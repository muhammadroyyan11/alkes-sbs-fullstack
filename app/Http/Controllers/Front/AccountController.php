<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $addresses = $user->addresses()->latest()->get();
        $primaryAddress = $addresses->firstWhere('is_primary', true) ?? $addresses->first();
        $orders = $user->orders()->with(['items', 'shipment'])->latest()->limit(4)->get();
        $orderStatusCounts = collect(['unpaid', 'processing', 'shipped', 'completed'])
            ->mapWithKeys(fn (string $status) => [
                $status => $user->orders()->forCustomerStatus($status)->count(),
            ]);

        $nameParts = collect(preg_split('/\s+/', trim($user->name)))->filter()->take(2);
        $initials = $nameParts->map(fn (string $name) => mb_strtoupper(mb_substr($name, 0, 1)))->implode('');
        $cartQuantity = collect($request->session()->get('cart', []))->sum('quantity');

        return view('front.account.index', compact(
            'user',
            'addresses',
            'primaryAddress',
            'orders',
            'orderStatusCounts',
            'initials',
            'cartQuantity',
        ));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:1000',
        ]);

        $request->user()->update($data);

        return back()->with('success', 'Profil berhasil diperbarui.');
    }
}
