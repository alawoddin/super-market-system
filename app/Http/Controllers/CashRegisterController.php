<?php

namespace App\Http\Controllers;

use App\Models\CashRegister;
use App\Services\CashRegisterService;
use Illuminate\Http\Request;

class CashRegisterController extends Controller
{
    public function __construct(private CashRegisterService $service)
    {
    }

    public function index()
    {
        $current = CashRegister::currentOpen();
        $history = CashRegister::query()->with('user')->latest()->paginate(20);

        return view('admin.cash-register.index', compact('current', 'history'));
    }

    public function open(Request $request)
    {
        $data = $request->validate([
            'opening_balance' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            $this->service->open((float) $data['opening_balance'], $data['notes'] ?? null);
        } catch (\Throwable $e) {
            return back()->with(['message' => $e->getMessage(), 'alert-type' => 'error']);
        }

        return back()->with(['message' => 'Cash register opened', 'alert-type' => 'success']);
    }

    public function close(Request $request)
    {
        $data = $request->validate([
            'actual_closing_balance' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            $this->service->close((float) $data['actual_closing_balance'], $data['notes'] ?? null);
        } catch (\Throwable $e) {
            return back()->with(['message' => $e->getMessage(), 'alert-type' => 'error']);
        }

        return back()->with(['message' => 'Cash register closed', 'alert-type' => 'success']);
    }

    public function show(CashRegister $cashRegister)
    {
        $cashRegister->load('user');

        return view('admin.cash-register.show', ['register' => $cashRegister]);
    }
}
