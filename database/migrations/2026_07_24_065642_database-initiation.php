<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("houses", function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string("house_number")->unique();
            $table->enum("house_status", ["occupied", "vacant"])->default("vacant");
            $table->timestamps();
        });

        Schema::create("residents", function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string("fullname");
            $table->string("ktp_image");
            $table->enum("resident_status", ["settler", "temporary"]);
            $table->string("phone_number");
            $table->enum("marriage_status", ["single", "married"]);
            $table->timestamps();
        });

        Schema::create("house_histories", function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid("house_id")->constrained("houses")->cascadeOnDelete();
            $table->foreignUuid("resident_id")->constrained("residents")->cascadeOnDelete();
            $table->date("start_date");
            $table->date("end_date")->nullable();
            $table->timestamps();
        });

        Schema::create("invoices", function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid("house_id")->constrained("houses")->cascadeOnDelete();
            $table->foreignUuid("resident_id")->constrained("residents")->cascadeOnDelete();
            $table->unsignedTinyInteger("month");
            $table->unsignedSmallInteger("year");
            $table->decimal("cleaning_bill", 12, 2)->default(15000);
            $table->decimal("security_bill", 12, 2)->default(100000);
            $table->enum("cleaning_bill_status", ["paid", "unpaid"])->default("unpaid");
            $table->enum("security_bill_status", ["paid", "unpaid"])->default("unpaid");
            $table->timestamps();

            $table->unique(['house_id', 'month', 'year']);
        });

        Schema::create("transactions", function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum("transaction_type", ["expenses", "income"]);
            $table->string("category");
            $table->decimal("amount", 12, 2);
            $table->date("transaction_date");
            $table->string("description")->nullable();
            $table->foreignUuid("invoice_id")->nullable()->constrained("invoices")->nullOnDelete(); 
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("transactions");
        Schema::dropIfExists("invoices");
        Schema::dropIfExists("house_histories");
        Schema::dropIfExists("residents");
        Schema::dropIfExists("houses");
    }
};