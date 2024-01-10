<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use App\Models\Driver;
use Illuminate\Support\Facades\Auth;
use App\Exports\OrdersExport;
use Maatwebsite\Excel\Facades\Excel;


class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $orders = Order::with(['user', 'vehicle', 'driver'])->get();

        return view('pemesanan', compact('orders'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $availableVehicles = Vehicle::whereNotIn('id', function($query) {
            $query->select('vehicle_id')
                ->from('orders')
                ->whereIn('status', ['pending', 'approved1', 'approved2']);
        })->get();
    
        return view('formPemesanan', compact('availableVehicles'));
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validasi data
        $request->validate([
            'driverName' => 'required|string',
            'orderDate' => 'required|date',
            'vehicleId' => 'required|exists:vehicles,id',
        ]);
        $driver = new Driver([
            'name' => $request->input('driverName'),
        ]);

        $driver->save();
        $order = new Order([
            'driver_id' => $driver->id, 
            'vehicle_id' => $request->input('vehicleId'),
            'order_date' => $request->input('orderDate'),
            'end_date' => $request->input('endDate'),
            'status' => 'pending',
        ]);

        $order->save();

        return redirect()->route('pemesanan');
    }

    public function approve(Order $order)
    {
        if (Auth::user()->role == 'approver1') {
            $order->status = 'approved1';
        } else if (Auth::user()->role == 'approver2' && $order->status == 'approved1') {
            $order->status = 'approved2';
        }
        $order->save();

        return back();
    }

    public function reject(Order $order)
    {
        if (Auth::user()->role == 'approver1' || (Auth::user()->role == 'approver2' && $order->status == 'approved1')) {
            $order->status = 'rejected';
            $order->save();
        }

        return back();
    }

    public function complete(Order $order)
    {
        $order->status = 'done';
        $order->push(); 
        
        return redirect()->back();
    }

    public function export()
    {
        return Excel::download(new OrdersExport, 'orders.xlsx');
    }


    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Order $order)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrderRequest $request, Order $order)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        //
    }
}
