<?php

namespace App\Filament\Student\Resources;

use App\Filament\Student\Resources\GenreResource\Pages;
use App\Filament\Student\Resources\GenreResource\RelationManagers;
use App\Models\Favorite;
use App\Models\Genre;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class GenreResource extends Resource
{
    protected static ?string $model = Genre::class;

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationGroup = 'Library';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('genre_title')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('genre_slug')
                    ->maxLength(255),
                Forms\Components\Textarea::make('genre_description')
                    ->required()
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
        ->schema([
            Grid::make(4)
                ->schema([
                    Section::make(fn ($record) => $record->genre_title)
                    ->schema([

                        TextEntry::make('genre_description')
                        ->label('')
                        ->prose(),
                    ])
                    ->columnSpan(3),
                    Section::make('')
                    ->schema([
                        TextEntry::make('created_at')
                        ->color('gray')
                        ->badge()
                        ->date(),
                        TextEntry::make('updated_at')
                        ->color('gray')
                        ->badge()
                        ->since(),
                    ])
                    ->columnSpan(1)
                ])
        ]);

    }

    public static function table(Table $table): Table
    {
        return $table

            ->columns([
                Tables\Columns\TextColumn::make('genre_title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('genre_description'),
                Tables\Columns\ImageColumn::make('favorites.user.avatar')
                ->label('Favorited by')
                ->circular()
                ->stacked()
                ->limit(3),

            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('Favorite')
                                        ->iconButton()
                                        ->action(function ($record) {
                                            // ...
                                            $user = Auth::user();
                                                if ($record->isFavoritedBy($user))
                                                {
                                                    $record->getFavorited($user)->delete();

                                                    Notification::make()
                                                    ->title("{$record->genre_title} removed from favorites")
                                                    ->icon('heroicon-o-bookmark')
                                                    ->danger()
                                                    ->send();
                                                }
                                                else
                                                {
                                                    Favorite::create([
                                                        'user_id' => $user->id,
                                                        'favorable_type' => Genre::class,
                                                        'favorable_id' => $record->id,
                                                    ]);


                                                    Notification::make()
                                                    ->title("{$record->genre_title} added to favorites")
                                                    ->icon('heroicon-o-bookmark')
                                                    ->success()
                                                    ->send();
                                                }


                                        })
                                        // ->disabled(fn ($record):bool => $record->isFavoritedBy(Auth::user()))
                                        ->icon(fn ($record) => ($record->isFavoritedBy(Auth::user())) ? 'heroicon-c-bookmark' : 'heroicon-o-bookmark'),
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
            'index' => Pages\ListGenres::route('/'),
            'create' => Pages\CreateGenre::route('/create'),
            'view' => Pages\ViewGenre::route('/{record}'),
            'edit' => Pages\EditGenre::route('/{record}/edit'),
        ];
    }
}
