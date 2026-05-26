<?php

namespace App\Service\Narratives;

use App\Dtos\PlayerNarrativeContext;
use App\Models\NarrativeTemplate;

class NarrativeTemplateService
{
    public function enrich(string $slug, array $placeholders): ?string
    {
        $template = NarrativeTemplate::where('slug', $slug)
            ->inRandomOrder()
            ->first();

        if (!$template) {
            return null;
        }

        return str_replace(
            array_keys($placeholders),
            array_values($placeholders),
            $template->body
        );
    }

    public function enrichForPlayer(string $slug, PlayerNarrativeContext $context): ?string
    {
        $placeholders = [
            '{player}'    => \App\Models\Player::find($context->playerId)?->full_name ?? 'Unknown Player',
            '{points}'    => $context->points,
            '{rebounds}'  => $context->rebounds,
            '{assists}'   => $context->assists,
            '{imp}'       => number_format($context->impPerStart, 2),
            '{plusMinus}' => ($context->plusMinus > 0 ? '+' : '') . $context->plusMinus,
            '{minutes}'   => round($context->playedSeconds / 60),
        ];

        return $this->enrich($slug, $placeholders);
    }
}
