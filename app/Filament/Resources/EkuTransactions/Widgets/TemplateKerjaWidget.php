<?php

namespace App\Filament\Resources\EkuTransactions\Widgets;

use App\Models\EkuTemplate;
use App\Support\CurrentUser;
use Filament\Widgets\Widget;

class TemplateKerjaWidget extends Widget
{
    protected string $view = 'filament.widgets.template-kerja-widget';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return CurrentUser::get()?->isUserPerbankan() ?? false;
    }

    public function getTemplateSetoran(): ?EkuTemplate
    {
        return EkuTemplate::current(EkuTemplate::JENIS_SETORAN);
    }

    public function getTemplatePenarikan(): ?EkuTemplate
    {
        return EkuTemplate::current(EkuTemplate::JENIS_PENARIKAN);
    }
}
