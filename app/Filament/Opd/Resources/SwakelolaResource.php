<?php

namespace App\Filament\Opd\Resources;

use App\Filament\Opd\Resources\SwakelolaResource\Pages;
use App\Filament\Opd\Resources\SwakelolaResource\RelationManagers;
use App\Models\swakelola;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SwakelolaResource extends Resource
{
    protected static ?string $model = swakelola::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Swakelola';

    public static function getModelLabel(): string
    {
        return 'Data Swakelola'; // Singular name
    }
    
    protected static ?string $pluralModelLabel = 'Swakelola';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('user_id')
                ->default(auth()->id())
                ->required(),
                Forms\Components\Section::make('Informasi Dasar')
                    ->schema([
                        Forms\Components\DatePicker::make('tanggal_dibuat')
                            ->label('Tanggal dibuat')
                            ->required()
                            ->default(now())
                            ->readOnly()  // Readonly di semua operasi (create & edit)
                            ->dehydrated() // Data tetap masuk ke database
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        Forms\Components\TextInput::make('nama_pekerjaan')
                            ->label('Nama Pekerjaan')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('kode_rup')
                            ->label('Kode RUP')
                            ->required()
                            ->numeric()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('pagu_rup')
                            ->label('Pagu RUP')
                            ->numeric()
                            ->prefix('Rp')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('kode_paket')
                            ->label('Kode Paket')
                            ->maxLength(255),
                    ])
                    ->columns(2),
                Forms\Components\Select::make('jenis_pengadaan')
                            ->label('Jenis Pengadaan')
                            ->options([
                                'Barang' => 'Barang',
                                'Pekerjaan Konstruksi' => 'Pekerjaan Konstruksi',
                                'Jasa Konsultansi' => 'Jasa Konsultansi',
                                'Jasa Lainnya' => 'Jasa Lainnya',
                                'Terintegrasi' => 'Terintegrasi',
                            ])
                            ->native(false),
                // Forms\Components\TextInput::make('surat_pesanan')
                //     ->maxLength(255)
                //     ->default(null),
                // BARIS 2: PDN/TKDN/IMPOR dan Nilainya
                // BARIS 1: Nilai Kontrak
                        Forms\Components\TextInput::make('nilai_kontrak')
                            ->label('Nilai Kontrak')
                            ->numeric()
                            ->required()
                            ->live(onBlur: true)
                            ->prefix('Rp')
                            ->placeholder('0')
                            ->columnSpanFull(),                        
                Forms\Components\FileUpload::make('realisasi')
                            ->label('Realisasi')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/jpg'])
                            ->maxSize(5120)
                            ->directory('realisasi')
                            ->downloadable()
                            ->openable()
                            ->helperText('Upload file JPG/PDF (Max: 5MB)'),
                // Forms\Components\TextInput::make('serah_terima'),
                // Forms\Components\Textarea::make('penilaian_kinerja')
                //     ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal_dibuat')
                    ->label('Tanggal dibuat')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('nama_pekerjaan')
                    ->label('Nama Pekerjaan')
                    ->searchable()
                    ->limit(50)
                    ->wrap(),

                Tables\Columns\TextColumn::make('kode_rup')
                    ->label('Kode RUP')
                    ->searchable(),

                Tables\Columns\TextColumn::make('pagu_rup')
                    ->label('Pagu RUP')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('nilai_kontrak')
                    ->label('Nilai Kontrak')
                    ->formatStateUsing(fn ($state) => $state ? 'Rp ' . number_format($state, 0, ',', '.') : '-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('jenis_pengadaan')
                    ->label('Jenis Pengadaan')
                    ->options([
                        'Barang' => 'Barang',
                        'Pekerjaan Konstruksi' => 'Pekerjaan Konstruksi',
                        'Jasa Konsultansi' => 'Jasa Konsultansi',
                        'Jasa Lainnya' => 'Jasa Lainnya',
                        'Terintegrasi' => 'Terintegrasi',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSwakelolas::route('/'),
            'create' => Pages\CreateSwakelola::route('/create'),
            'edit' => Pages\EditSwakelola::route('/{record}/edit'),
        ];
    }
}
