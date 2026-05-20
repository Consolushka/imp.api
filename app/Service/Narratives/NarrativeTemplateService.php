<?php

namespace App\Service\Narratives;

use App\Dtos\PlayerNarrativeContext;
use App\Models\NarrativeTemplate;

class NarrativeTemplateService
{
    public function enrich(string $slug, PlayerNarrativeContext $context): ?string
    {
        $template = NarrativeTemplate::where('slug', $slug)
            ->inRandomOrder()
            ->first();

        if (!$template) {
            return null;
        }

        return $this->hydrate($template->body, $context);
    }
private function hydrate(string $body, PlayerNarrativeContext $context): string
{
    $placeholders = [
        '{player}' => \App\Models\Player::find($context->playerId)?->name ?? 'Unknown Player',
        '{points}' => $context->points,
        '{rebounds}' => $context->rebounds,
        '{assists}' => $context->assists,
        '{imp}' => number_format($context->impPerStart, 2),
        '{plusMinus}' => ($context->plusMinus > 0 ? '+' : '') . $context->plusMinus,
        '{minutes}' => round($context->playedSeconds / 60),
    ];

    return str_replace(
        array_keys($placeholders),
        array_values($placeholders),
        $body
    );
}
}
