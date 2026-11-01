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
        $currentProfit = $current ? $this->service->summary($current)['net_profit'] : null;
        $history = CashRegister::query()->with('user')->latest()->paginate(20);
        $profits = $history->getCollection()
            ->mapWithKeys(fn (CashRegister $r) => [$r->id => $this->service->summary($r)['net_profit']]);

        return view('admin.cash-register.index', compact('current', 'currentProfit', 'history', 'profits'));
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
            'notes' => ['nullable', 'string'],
        ]);

        try {
            $register = $this->service->close($data['notes'] ?? null);
        } catch (\Throwable $e) {
            return back()->with(['message' => $e->getMessage(), 'alert-type' => 'error']);
        }

        $profit = $this->service->summary($register)['net_profit'];

        return redirect()->route('cash-register.show', $register)->with([
            'message' => 'Cash register closed with '.money($register->actual_closing_balance).'. Day profit: '.money($profit),
            'alert-type' => 'success',
        ]);
    }

    public function show(CashRegister $cashRegister)
    {
        $cashRegister->load('user');

        return view('admin.cash-register.show', [
            'register' => $cashRegister,
            'summary' => $this->service->summary($cashRegister),
        ]);
    }
}
