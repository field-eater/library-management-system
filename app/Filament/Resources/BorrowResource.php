<?php

namespace App\Filament\Resources;

use App\Enums\BookCopyStatusEnum;
use App\Enums\BorrowStatusEnum;
use App\Filament\Resources\BookResource\RelationManagers\BorrowsRelationManager;
use App\Filament\Resources\BorrowResource\Pages;
use App\Filament\Resources\BorrowResource\RelationManagers;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Borrow;
use App\Models\Student;
use App\Models\User;
use Faker\Provider\ar_EG\Text;
use Illuminate\Support\Collection;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords\Tab;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextColumn\TextColumnSize;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Validation\Rules\Enum;

class BorrowResource extends Resource
{
    protected static ?string $model = Borrow::class;
    protected static ?string $navigationIcon = 'heroicon-o-queue-list';
    protected static ?string $navigationGroup = 'Book Management';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
               Grid::make(1)
               ->schema([
                    Select::make('student_id')
                    ->options(Student::all()->pluck('student_number', 'id'))
                    ->getOptionLabelFromRecordUsing(fn (Model $record): string => Book::find($record->book_id)->book_name)
                    ->required(),
                    Select::make('book_copy_id')
                    ->label('Book')
                    ->preload()
                    ->options(Book::all()->pluck('book_name', 'id'))
                    ->searchable()
                    ->disableOptionWhen(function (string $value):bool
                    {
                        $copy = BookCopy::where('book_id', $value)->where('status', BookCopyStatusEnum::Available)->count();
                        if ($copy > 0)
                        {
                            return false;
                        }
                        return true;
                    })
                    ->required(),

                    DatePicker::make('date_borrowed')
                    ->required(),
               ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
                TextColumn::make('student_id')
                ->label('Student Name')
                ->formatStateUsing(function (User $user, $state)
                {
                    $student = Student::find($state);
                    $user = $user->find($student->user_id);
                    return "{$user->first_name} {$user->last_name}";
                }),
                TextColumn::make('book_copy_id')
                ->description(fn ($state) => implode(BookCopy::where('id',$state)->pluck('copy_id')->toArray()))
                ->formatStateUsing(function ($state)
                {
                    $copy = BookCopy::find($state)->first();
                    $bookName = Book::where('id', $copy->book_id)->first();
                    return $bookName->book_name;
                })
                ->badge()

                ->label('Borrowed Book'),
                // ->hiddenOn(BorrowsRelationManager::class),
                TextColumn::make('date_borrowed')
                ->sortable()
                ->date(),
                TextColumn::make('date_returned')
                ->default('')
                // ->hiddenOn(BorrowsRelationManager::class)
                ->date(),
                TextColumn::make('return_status')
                ->label('Status')
                ->badge()

            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('return')
                ->icon('heroicon-o-hand-raised')
                ->visible(fn ($record) => $record->date_returned == NULL)
                ->form([
                    DatePicker::make('date_returned'),
                ])
                ->modalWidth('sm')
                ->action(function ($record, Array $data) {
                    $record->date_returned = $data['date_returned'];
                    $record->return_status = BorrowStatusEnum::Returned;
                    $record->save();

                    $bookCopy = BookCopy::where('id', $record->book_copy_id)->first();
                    $bookCopy->status = BookCopyStatusEnum::Available;
                    $bookCopy->save();

                    Notification::make()
                        ->title('Saved successfully')
                        ->success()
                        ->send();
                }),

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
            'index' => Pages\ListBorrows::route('/'),
            'create' => Pages\CreateBorrow::route('/create'),
            // 'edit' => Pages\EditBorrow::route('/{record}/edit'),
        ];
    }
}
