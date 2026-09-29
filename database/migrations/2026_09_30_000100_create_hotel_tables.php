<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
        });

        Schema::create('room_types', function (Blueprint $table) {
            $table->string('code', 10)->primary();
            $table->string('name');
            $table->unsignedBigInteger('rate');
            $table->unsignedSmallInteger('sort')->default(0);
        });

        Schema::create('rooms', function (Blueprint $table) {
            $table->string('no', 10)->primary();
            $table->string('room_type_code', 10);
            $table->unsignedTinyInteger('floor');
            $table->string('status', 4)->default('VC'); // VC, VD, OC, OD, OOO
            $table->string('attendant')->nullable();
            $table->string('ooo_note')->nullable();
            $table->foreign('room_type_code')->references('code')->on('room_types');
        });

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedSmallInteger('sort')->default(0);
        });

        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('guest');
            $table->string('phone')->nullable();
            $table->string('idno')->nullable();
            $table->string('nationality')->default('Indonesia');
            $table->string('room_type_code', 10);
            $table->date('arrival');
            $table->date('departure');
            $table->unsignedTinyInteger('adults')->default(1);
            $table->unsignedBigInteger('rate');
            $table->string('source')->default('Telepon');
            $table->string('status', 20)->default('Confirmed'); // Confirmed, In-house, Checked-out, Cancelled, No-show
            $table->string('room_no', 10)->nullable();
            $table->string('note')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
            $table->foreign('room_type_code')->references('code')->on('room_types');
            $table->index(['status', 'arrival']);
        });

        Schema::create('folio_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('dept', 30);
            $table->string('description');
            $table->bigInteger('amount');
            $table->string('created_by')->nullable();
            $table->timestamps();
            $table->index(['date', 'dept']);
        });

        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->string('category', 30);
            $table->string('name');
            $table->unsignedBigInteger('price');
            $table->string('note')->nullable();
            $table->boolean('active')->default(true);
        });

        Schema::create('pos_orders', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->json('items');
            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('tax');
            $table->string('payment', 30);
            $table->string('room_no', 10)->nullable();
            $table->string('table_no', 20)->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
            $table->index('date');
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->date('biz_date');
            $table->string('user_name');
            $table->string('role', 40);
            $table->string('action', 60);
            $table->text('detail');
            $table->boolean('ok')->default(true);
            $table->timestamp('created_at')->nullable();
            $table->index('user_name');
        });

        Schema::create('night_audits', function (Blueprint $table) {
            $table->id();
            $table->date('biz_date');
            $table->string('performed_by');
            $table->json('stats')->nullable();
            $table->timestamps();
        });

        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->string('room_no', 10);
            $table->text('text');
            $table->date('date');
            $table->string('reported_by');
            $table->boolean('open')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['work_orders', 'night_audits', 'activity_logs', 'pos_orders', 'menu_items', 'folio_lines', 'reservations', 'students', 'rooms', 'room_types', 'settings'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
