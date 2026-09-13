<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class Shipment extends Model{protected $fillable=['order_id','courier','service','tracking_number','status','shipped_at','delivered_at'];protected $casts=['shipped_at'=>'datetime','delivered_at'=>'datetime'];public function order(){return $this->belongsTo(Order::class);}}
