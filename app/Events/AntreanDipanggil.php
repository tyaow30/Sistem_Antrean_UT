<?php

namespace App\Events;

use App\Models\Antrean;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AntreanDipanggil implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $antrean;

    public function __construct(Antrean $antrean)
    {
        // Load relasi loket agar nama/nomor loket terbawa ke display
        $this->antrean = $antrean->load('loketMelayani');
    }

    public function broadcastOn(): array
    {
        // Channel public berdasarkan ID Gerai
        return [
            new Channel('display-gerai.' . $this->antrean->gerai_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'antrean.dipanggil';
    }
}