<?php

namespace App\Filament\Pages;

use App\Enums\CodeKind;
use App\Filament\Support\AdminAccess;
use App\Jobs\GenerateCodeBatch;
use App\Models\CodeBatch;
use App\Services\Economy\CodeExportStore;
use App\Services\Economy\CodeGenerator;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class GenerateCodes extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationLabel = 'Kod Üret';

    protected static ?string $title = 'Kod üret';

    protected static ?string $navigationGroup = 'Ekonomi';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.generate-codes';

    public ?array $data = [];

    public ?string $downloadUrl = null;

    public ?string $notice = null;

    public static function canAccess(): bool
    {
        return AdminAccess::admin();
    }

    public function mount(): void
    {
        $this->form->fill([
            'adet' => 10,
            'tur' => CodeKind::RuhTasi->value,
            'deger' => 10,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('adet')->label('Adet')->numeric()->minValue(1)->maxValue(50000)->required(),
                Select::make('tur')->label('Tür')->options([
                    CodeKind::RuhTasi->value => 'Ruh taşı',
                    CodeKind::Vip->value => 'VIP',
                ])->required(),
                TextInput::make('deger')->label('Değer')->helperText('Taş adedi veya VIP gün')->numeric()->minValue(1)->required(),
                DateTimePicker::make('son_kullanma')->label('Son kullanma'),
                TextInput::make('ad')->label('Parti adı')->required()->maxLength(120),
                Textarea::make('not')->label('Not')->rows(2)->maxLength(500),
            ])
            ->columns(2)
            ->statePath('data');
    }

    public function generate(CodeGenerator $generator, CodeExportStore $store): void
    {
        $state = $this->form->getState();
        $count = (int) $state['adet'];
        $kind = CodeKind::from($state['tur']);
        $value = (int) $state['deger'];
        $expires = filled($state['son_kullanma'] ?? null) ? Carbon::parse($state['son_kullanma']) : null;

        if ($count >= 10000) {
            $batch = CodeBatch::query()->create([
                'ad' => $state['ad'],
                'tur' => $kind,
                'deger' => $value,
                'son_kullanma' => $expires,
                'adet' => $count,
                'olusturan_user_id' => auth()->id(),
                'not' => $state['not'] ?? null,
            ]);

            GenerateCodeBatch::dispatch($batch->id);
            $this->downloadUrl = null;
            $this->notice = 'Üretim kuyruğa alındı. Hazır olunca Kod Partileri satırındaki tek seferlik bağlantıdan inecek. Dosya bir kez iner, sonra silinir.';

            Notification::make()->title('Kuyruğa alındı')->success()->send();

            return;
        }

        $result = $generator->generate($count, $kind, $value, $expires, $state['ad'], auth()->user(), $state['not'] ?? null);
        $export = $store->stash($result['batch'], $generator->csv($result['codes']));
        $this->downloadUrl = route('admin.codes.csv', ['token' => $export->token]);
        $this->notice = 'Düz kodlar yalnızca bir kez indirilir ve hiçbir yerde saklanmaz. Sayfayı yenilerseniz bu bağlantı kaybolur.';

        Notification::make()->title($result['batch']->adet.' kod hazır')->success()->send();
    }
}
