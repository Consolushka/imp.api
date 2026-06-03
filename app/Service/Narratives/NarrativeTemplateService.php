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
            '{imp}'       => number_format($context->impPerStart, 1),
            '{plusMinus}' => ($context->plusMinus > 0 ? '+' : '') . $context->plusMinus,
            '{minutes}'   => round($context->playedSeconds / 60),
            '{value}'     => $this->formatValueForPlayer($slug, $context),
        ];

        return $this->enrich($slug, $placeholders);
    }

    public function formatValueForPlayer(string $slug, PlayerNarrativeContext $context): string
    {
        return match ($slug) {
            'lone_atlas', 'difference_maker', 'glue_guy', 'sinkhole', 'spark_plug', 'unsung_hero', 'forgotten_pillar', 'carried_to_victory' => number_format($context->impPerStart, 1) . ' IMP',
            'empty_stats' => $context->points . ' PTS',
            'triple_double' => 'Triple-Double',
            'cardio_session' => round($context->playedSeconds / 60) . ' MIN',
            'ice_cold', 'sniper' => (int) ($context->fieldGoalsPercentage * 100) . '% FG',
            default => number_format($context->impPerStart, 1) . ' IMP',
        };
    }
}
