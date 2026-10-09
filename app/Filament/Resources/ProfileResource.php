<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProfileResource\Pages;
use App\Filament\Resources\ProfileResource\RelationManagers;
use App\Models\Profile;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProfileResource extends Resource
{
    // Menghubungkan Resource ini dengan Eloquent Model Profile
    protected static ?string $model = Profile::class;

    // Menentukan ikon menu navigasi di sidebar panel Filament
    protected static ?string $navigationIcon = 'heroicon-o-user';

    /**
     * INSIGHT & KNOWLEDGE:
     * Method form() mendefinisikan skema input (UI Form) secara deklaratif.
     * Filament otomatis menangani validasi, rendering HTML, dan state management.
     * Catatan: Ada sedikit typo pada input pertama 'make', yang seharusnya 'name' agar sesuai dengan kolom database.
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('headline')
                    ->required()
                    ->maxLength(255),
                Textarea::make('bio')
                    ->required()
                    ->columnSpanFull(), // Membuat elemen memanjang penuh 1 baris grid
                FileUpload::make('avatar')
                    ->image() // Membatasi hanya file gambar yang bisa di-upload
                    ->directory('avatars'), // Folder penyimpanan di storage/app/public/avatars
                KeyValue::make('social_links')
                    ->keyLabel('Platform') // Label untuk kolom key (misal: GitHub, LinkedIn)
                    ->valueLabel('URL') // Label untuk kolom value (misal: https://...)
                    ->columnSpanFull(),
            ]);
    }

    /**
     * INSIGHT & KNOWLEDGE:
     * Method table() mengatur tampilan data dalam bentuk baris tabel di halaman Index (Dashboard list).
     * Memungkinkan pencarian (searchable) dan aksi CRUD instan.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar'), // Menampilkan preview gambar avatar
                TextColumn::make('name')->searchable(), // Kolom nama yang bisa dicari
                TextColumn::make('headline')->searchable(),
                TextColumn::make('updated_at')->dateTime() // Menampilkan timestamp pembaruan terakhir
            ])
            ->filters([
                // Tempat menambahkan filter kustom (misal berdasarkan status/tanggal)
            ])
            ->actions([
                Tables\Actions\EditAction::make(), // Tombol aksi untuk mengedit data per baris
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
            // Tempat mendaftarkan relasi data turunan (jika ada tabel lain yang terhubung)
        ];
    }

    /**
     * INSIGHT & KNOWLEDGE:
     * getPages() memetakan routing halaman internal Filament Resource.
     * 'index' untuk list data, 'create' untuk form tambah, 'edit' untuk form ubah data.
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProfiles::route('/'),
            'create' => Pages\CreateProfile::route('/create'),
            'edit' => Pages\EditProfile::route('/edit/{record}'),
        ];
    }
}
