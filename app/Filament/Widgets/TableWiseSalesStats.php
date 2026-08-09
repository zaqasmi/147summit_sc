<?php

namespace App\Filament\Widgets;

use App\Models\SnookerTable;
use App\Services\ReportService;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class TableWiseSalesStats extends TableWidget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    /** @var array<int, array<string, mixed>>|null */
    private ?array $tableRows = null;

    public function table(Table $table): Table
    {
        return $table
            ->striped()
            ->heading('Table-wise stats')
            ->description('Daily table sale, frames, sessions, and due amount for the selected date.')
            ->query(fn (): Builder => SnookerTable::query()->orderBy('number'))
            ->paginated(false)
            ->poll('30s')
            ->columns([
                TextColumn::make('name')
                    ->label('Table')
                    ->badge()
                    ->sortable(),
                TextColumn::make('sessions')
                    ->label('Sessions')
                    ->getStateUsing(fn (SnookerTable $record): int => (int) $this->tableRow($record)['sessions'])
                    ->alignCenter(),
                TextColumn::make('frames')
                    ->label('Frames')
                    ->getStateUsing(fn (SnookerTable $record): int => (int) $this->tableRow($record)['frames'])
                    ->alignCenter(),
                TextColumn::make('sales')
                    ->label('Sale')
                    ->getStateUsing(fn (SnookerTable $record): string => $this->money($this->tableRow($record)['sales']))
                    ->alignEnd(),
                TextColumn::make('due')
                    ->label('Due')
                    ->getStateUsing(fn (SnookerTable $record): string => $this->money($this->tableRow($record)['due']))
                    ->color(fn (SnookerTable $record): string => ((float) $this->tableRow($record)['due'] > 0) ? 'danger' : 'success')
                    ->alignEnd(),
            ]);
    }

    /**
     * @return array{sessions: int, frames: int, sales: float, due: float}
     */
    private function tableRow(SnookerTable $table): array
    {
        return $this->tableRows()[$table->id] ?? [
            'sessions' => 0,
            'frames' => 0,
            'sales' => 0.0,
            'due' => 0.0,
        ];
    }

    /**
     * @return array<int, array{sessions: int, frames: int, sales: float, due: float}>
     */
    private function tableRows(): array
    {
        if ($this->tableRows !== null) {
            return $this->tableRows;
        }

        $report = app(ReportService::class)->daily($this->selectedDate(), withCapital: false);

        return $this->tableRows = collect($report['table_sales'])
            ->mapWithKeys(fn (array $row): array => [
                $row['table']->id => [
                    'sessions' => (int) $row['sessions'],
                    'frames' => (int) $row['frames'],
                    'sales' => (float) $row['sales'],
                    'due' => (float) $row['due'],
                ],
            ])
            ->all();
    }

    private function selectedDate(): Carbon
    {
        $date = $this->pageFilters['date'] ?? null;

        try {
            return Carbon::parse($date ?: today())->startOfDay();
        } catch (\Throwable) {
            return today();
        }
    }

    private function money(float|int|string|null $amount): string
    {
        return 'Rs ' . number_format((float) $amount, 2);
    }
}
