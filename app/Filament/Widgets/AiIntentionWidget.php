<?php

namespace App\Filament\Widgets;

use App\Models\DailyPlan;
use Filament\Notifications\Notification;

class AiIntentionWidget extends BaseWidget
{
    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.ai-intention-widget';

    public bool $isGenerating = false;

    public function getViewData(): array
    {
        $plan = DailyPlan::todayOrCreate();

        return [
            'intention' => $plan->ai_morning_prompt,
            'isGenerating' => $this->isGenerating,
            'hasPlan' => (bool) $plan->id,
        ];
    }

    public function generate(): void
    {
        $this->isGenerating = true;

        try {
            $plan = DailyPlan::todayOrCreate();

            if (class_exists(\App\Services\AiService::class)) {
                $aiService = app(\App\Services\AiService::class);
                $intention = $aiService->generateMorningIntention($plan);
                $plan->update(['ai_morning_prompt' => $intention]);
            } else {
                $plan->update([
                    'ai_morning_prompt' => 'Today, focus on what matters most. Take one step toward each of your active goals and trust the process. Your consistency is building something remarkable.',
                ]);
            }

            Notification::make()
                ->title('Morning intention generated')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Could not generate intention')
                ->body('Please try again in a moment.')
                ->danger()
                ->send();
        } finally {
            $this->isGenerating = false;
        }
    }
}
