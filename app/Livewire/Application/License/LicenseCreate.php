<?php

namespace App\Livewire\Application\License;

use App\Models\License;
use Livewire\Component;

class LicenseCreate extends Component
{
    public $license_key, $expires_at;
    public $application;
    public $license_quantity;

    protected $rules = [
        'license_key' => 'required',
        'expires_at' => 'required|date'
    ];

    public function mount($application)
    {
        $this->license_quantity = 1;
    }

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
        if ($this->license_quantity < 1) {
            $this->license_quantity = 1;
        }
        $this->license_key = strtoupper($this->license_key);
        $this->license_key = preg_replace('/[^A-Za-z0-9-]/', '', $this->license_key);
    }

    public function createLicense()
    {
        try {
            $this->validate();
            $lastIndex = strlen($this->license_key);
            $this->license_key = substr_replace($this->license_key, "-XXXX", $lastIndex, 2);

            for ($i = 0; $i < $this->license_quantity; $i++) {
                $licenseMasked = preg_replace_callback('/X+/', function ($matches) {
                    return strtoupper(bin2hex(random_bytes(strlen($matches[0]))));
                }, $this->license_key);

                $created = License::create([
                    'license_key' => $licenseMasked,
                    'expires_at' => $this->expires_at,
                    'application_id' => $this->application->id,
                ]);
                
                $created->application->increment('license_count', 1);
                
                if (!$created) {
                    session()->flash('error', 'License could not be created');
                    return;
                }
            }

            session()->flash('success', 'Licenses created successfully');
            $this->reset('license_key', 'expires_at', 'license_quantity');
            $this->dispatch('license-created');
        } catch (\Throwable $th) {
            session()->flash('error', $th->getMessage());
        }
    }
    public function render()
    {
        return view('livewire.application.license.license-create');
    }
}
