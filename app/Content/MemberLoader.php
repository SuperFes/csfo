<?php

namespace App\Content;

use App\Enums\Chamber;
use App\Enums\EntryStatus;
use App\Enums\MemberInclusion;
use App\Enums\VotePosition;
use Illuminate\Validation\Rule;

/**
 * Loads content/members/*.yaml: who funds a member and how they voted.
 */
class MemberLoader
{
    use ReadsYamlContent;

    /**
     * @param  list<string>|null  $entrySlugs  when given, related_entries must all exist
     * @param  list<string>|null  $policySlugs  when given, votes.*.policy must all exist
     * @return array<string, array<string, mixed>> keyed by slug, in file-name order
     *
     * @throws InvalidContentException
     */
    public function load(string $directory, ?array $entrySlugs = null, ?array $policySlugs = null): array
    {
        $errors = [];
        $members = [];

        foreach (glob(rtrim($directory, '/').'/*.yaml') ?: [] as $path) {
            $file = basename($path);

            try {
                $member = $this->normalize($this->readValidated($path, $this->rules()));
            } catch (InvalidContentException $e) {
                $errors += $e->errors;

                continue;
            }

            if ($problems = $this->crossCheck($member, $entrySlugs, $policySlugs)) {
                $errors[$file] = $problems;

                continue;
            }

            $members[$member['slug']] = $member;
        }

        if ($errors) {
            throw new InvalidContentException($errors);
        }

        return $members;
    }

    /**
     * @param  array<string, mixed>  $member
     * @return list<string>
     */
    private function crossCheck(array $member, ?array $entrySlugs, ?array $policySlugs): array
    {
        $problems = [];
        $votedPolicies = array_values(array_filter(array_column($member['votes'], 'policy')));

        // The published rule: a blocker page must point at the policy they blocked.
        if (in_array(MemberInclusion::Blocker->value, $member['inclusion'], true) && ! $votedPolicies) {
            $problems[] = 'Blockers need at least one vote tied to a policy.';
        }

        if (in_array(MemberInclusion::Leadership->value, $member['inclusion'], true) && ! $member['role']) {
            $problems[] = 'Leadership members need a role.';
        }

        if ($entrySlugs !== null && $missing = array_diff($member['related_entries'], $entrySlugs)) {
            $problems[] = 'Unknown related entries: '.implode(', ', $missing);
        }

        if ($policySlugs !== null && $missing = array_diff($votedPolicies, $policySlugs)) {
            $problems[] = 'Unknown vote policies: '.implode(', ', array_unique($missing));
        }

        return $problems;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalize(array $data): array
    {
        $inclusion = array_values(array_unique($data['inclusion']));

        return [
            'slug' => $data['slug'],
            'name' => trim($data['name']),
            'party' => $data['party'],
            'chamber' => $data['chamber'],
            'chamber_label' => Chamber::from($data['chamber'])->label(),
            'state' => $data['state'],
            'district' => $data['district'] ?? null,
            'role' => $this->optionalText($data['role'] ?? null),
            'inclusion' => $inclusion,
            'inclusion_labels' => array_map(fn (string $i) => MemberInclusion::from($i)->label(), $inclusion),
            'status' => $data['status'],
            'review_note' => $this->optionalText($data['review_note'] ?? null),
            'summary' => trim($data['summary']),
            'funding' => array_map(fn (array $f) => [
                'label' => trim($f['label']),
                'value' => (string) $f['value'],
                'as_of' => (string) $f['as_of'],
                'source_url' => $f['source_url'],
            ], $data['funding'] ?? []),
            'industries' => $this->normalizeDonors($data['industries'] ?? []),
            'employers' => $this->normalizeDonors($data['employers'] ?? []),
            'votes' => $this->normalizeVotes($data['votes'] ?? []),
            'related_entries' => array_values(array_unique($data['related_entries'] ?? [])),
            'sources' => $this->normalizeSources($data['sources']),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, string>>
     */
    private function normalizeDonors(array $rows): array
    {
        return array_map(fn (array $r) => [
            'name' => trim($r['name']),
            'amount' => (string) $r['amount'],
            'period' => (string) $r['period'],
            'source_url' => $r['source_url'],
        ], $rows);
    }

    /**
     * @param  list<array<string, mixed>>  $votes
     * @return list<array<string, mixed>>
     */
    private function normalizeVotes(array $votes): array
    {
        $votes = array_map(fn (array $v) => [
            'date' => $v['date'],
            'title' => trim($v['title']),
            'position' => $v['position'],
            'position_label' => VotePosition::from($v['position'])->label(),
            'explanation' => trim($v['explanation']),
            'url' => $v['url'],
            'policy' => $v['policy'] ?? null,
        ], $votes);

        usort($votes, fn (array $a, array $b) => $b['date'] <=> $a['date']);

        return $votes;
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'slug' => ['required', 'string', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'max:120'],
            'name' => ['required', 'string', 'max:120'],
            'party' => ['required', Rule::in(['D', 'I'])],
            'chamber' => ['required', Rule::enum(Chamber::class)],
            'state' => ['required', 'string', 'regex:/^[A-Z]{2}$/'],
            'district' => ['nullable', 'prohibited_unless:chamber,house', 'string', 'max:10'],
            'role' => ['nullable', 'string', 'max:120'],
            'inclusion' => ['required', 'array', 'min:1'],
            'inclusion.*' => [Rule::enum(MemberInclusion::class)],
            'status' => ['required', Rule::enum(EntryStatus::class)],
            'review_note' => ['required_if:status,needs-review', 'nullable', 'string'],
            'summary' => ['required', 'string'],
            'funding' => ['nullable', 'array'],
            'funding.*.label' => ['required', 'string'],
            'funding.*.value' => ['required'],
            'funding.*.as_of' => ['required'],
            'funding.*.source_url' => ['required', 'url:https,http'],
            'industries' => ['nullable', 'array'],
            'industries.*.name' => ['required', 'string'],
            'industries.*.amount' => ['required'],
            'industries.*.period' => ['required'],
            'industries.*.source_url' => ['required', 'url:https,http'],
            'employers' => ['nullable', 'array'],
            'employers.*.name' => ['required', 'string'],
            'employers.*.amount' => ['required'],
            'employers.*.period' => ['required'],
            'employers.*.source_url' => ['required', 'url:https,http'],
            'votes' => ['nullable', 'array'],
            'votes.*.date' => ['required', 'date_format:Y-m-d'],
            'votes.*.title' => ['required', 'string'],
            'votes.*.position' => ['required', Rule::enum(VotePosition::class)],
            'votes.*.explanation' => ['required', 'string'],
            'votes.*.url' => ['required', 'url:https,http'],
            'votes.*.policy' => ['nullable', 'string'],
            'related_entries' => ['nullable', 'array'],
            'related_entries.*' => ['string'],
            ...$this->sourceRules(),
        ];
    }
}
