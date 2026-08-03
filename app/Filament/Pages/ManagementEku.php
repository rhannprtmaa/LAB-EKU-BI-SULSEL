<?php

namespace App\Filament\Pages;

use App\Models\EkuDeadline;
use App\Models\EkuTemplate;
use App\Support\CurrentUser;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class ManagementEku extends Page implements HasForms
{
    use InteractsWithForms;


    protected string $view = 'filament.pages.management-eku';


    protected static string|\BackedEnum|null $navigationIcon =
        'heroicon-o-cog-6-tooth';


    protected static ?string $navigationLabel = 'Management EKU';

    protected static ?string $title = 'Management EKU';



    public static function canAccess(): bool
    {
        return CurrentUser::get()?->isAdminBi() ?? false;
    }





    public $tanggal_deadline;

    public $keterangan_deadline;





    public ?array $data = [];




    public function mount(): void
    {
        $deadline = EkuDeadline::current();


        $this->tanggal_deadline =
            $deadline?->batas_waktu;


        $this->keterangan_deadline =
            $deadline?->keterangan;



        $this->form->fill();
    }







    public function form(Schema $schema): Schema
    {
        return $schema

            ->components([


                Section::make('Template Setoran')
                    ->schema([

                        FileUpload::make('file_setoran')

                            ->label(
                                'File Template Setoran (Excel)'
                            )

                            ->disk('public')

                            ->directory('template-eku')

                            ->acceptedFileTypes([
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            ])

                            ->maxSize(5120),


                    ]),




                Section::make('Template Penarikan')
                    ->schema([


                        FileUpload::make('file_penarikan')

                            ->label(
                                'File Template Penarikan (Excel)'
                            )

                            ->disk('public')

                            ->directory('template-eku')

                            ->acceptedFileTypes([
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            ])

                            ->maxSize(5120),


                    ]),



            ])

            ->statePath('data');
    }








    public function simpanDeadline(): void
    {

        $this->validate([

            'tanggal_deadline' =>
                'required',

            'keterangan_deadline' =>
                'nullable|string|max:255',

        ]);



        EkuDeadline::create([

            'batas_waktu' =>
                $this->tanggal_deadline,


            'keterangan' =>
                $this->keterangan_deadline,


            'created_by' =>
                Auth::id(),

        ]);



        Notification::make()

            ->title(
                'Batas Pengajuan EKU berhasil disimpan'
            )

            ->success()

            ->send();



    }









    public function hapusDeadline($id): void
    {

        EkuDeadline::findOrFail($id)
            ->delete();



        Notification::make()

            ->title(
                'Batas Pengajuan EKU berhasil dihapus'
            )

            ->success()

            ->send();

    }









    public function save(): void
    {

        $state =
            $this->form->getState();




        if (!empty($state['file_setoran'])) {


            EkuTemplate::create([

                'nama_file' =>
                    basename(
                        $state['file_setoran']
                    ),


                'jenis' =>
                    EkuTemplate::JENIS_SETORAN,


                'file_path' =>
                    $state['file_setoran'],


                'uploaded_by' =>
                    Auth::id(),

            ]);

        }





        if (!empty($state['file_penarikan'])) {


            EkuTemplate::create([


                'nama_file' =>
                    basename(
                        $state['file_penarikan']
                    ),


                'jenis' =>
                    EkuTemplate::JENIS_PENARIKAN,


                'file_path' =>
                    $state['file_penarikan'],


                'uploaded_by' =>
                    Auth::id(),

            ]);

        }





        Notification::make()

            ->title(
                'Template EKU berhasil diperbarui'
            )

            ->success()

            ->send();




        $this->form->fill();

    }

    public function hapusTemplate($id): void
    {
        EkuTemplate::findOrFail($id)
            ->delete();

        Notification::make()

            ->title(
                'Template EKU berhasil dihapus'
            )
            ->success()
            ->send();

    }

    public function templateSetoran(): ?EkuTemplate
    {
        return EkuTemplate::current(
            EkuTemplate::JENIS_SETORAN
        );
    }

    public function templatePenarikan(): ?EkuTemplate
    {
        return EkuTemplate::current(
            EkuTemplate::JENIS_PENARIKAN
        );
    }

    public function batasSaatIni(): ?EkuDeadline
    {
        return EkuDeadline::current();
    }

}
