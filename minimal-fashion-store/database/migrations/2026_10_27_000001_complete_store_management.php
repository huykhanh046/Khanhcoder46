<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collections', function (Blueprint $t) {
            $t->id();
            $t->string('name', 100);
            $t->string('slug', 120)->unique();
            $t->text('description')->nullable();
            $t->string('image_path')->nullable();
            $t->boolean('active')->default(true)->index();
            $t->unsignedInteger('position')->default(0);
            $t->timestamps();
        });
        Schema::table('products', fn (Blueprint $t) => $t->foreignId('collection_id')->nullable()->constrained()->nullOnDelete());
        foreach (DB::table('products')->whereNotNull('collection')->distinct()->pluck('collection') as $name) {
            $slug = Str::slug($name) ?: 'collection';
            $base = $slug;
            $suffix = 1;
            while (DB::table('collections')->where('slug', $slug)->exists()) {
                $slug = $base.'-'.$suffix++;
            }
            $id = DB::table('collections')->insertGetId(['name' => $name, 'slug' => $slug, 'description' => 'Bộ sưu tập '.$name, 'image_path' => '/images/collection.svg', 'active' => true, 'position' => 0, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('products')->where('collection', $name)->update(['collection_id' => $id]);
        }
        Schema::table('products', fn (Blueprint $t) => $t->dropColumn('collection'));
        Schema::table('orders', function (Blueprint $t) {
            $t->string('shipping_method')->default('standard');
            $t->string('shipping_label')->default('Giao hàng tiêu chuẩn');
        });
        Schema::table('return_requests', fn (Blueprint $t) => $t->timestamp('restocked_at')->nullable());
        Schema::create('payment_callbacks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $t->char('fingerprint', 64)->unique();
            $t->string('transaction_reference', 100)->nullable()->index();
            $t->string('provider_reference', 100)->nullable();
            $t->string('response_code', 10);
            $t->boolean('signature_valid');
            $t->string('verdict', 150);
            $t->unsignedInteger('attempts')->default(0);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_callbacks');
        Schema::table('return_requests', fn (Blueprint $t) => $t->dropColumn('restocked_at'));
        Schema::table('orders', fn (Blueprint $t) => $t->dropColumn(['shipping_method', 'shipping_label']));
        Schema::table('products', fn (Blueprint $t) => $t->string('collection')->nullable()->index());
        foreach (DB::table('collections')->get() as $collection) {
            DB::table('products')->where('collection_id', $collection->id)->update(['collection' => $collection->name]);
        }
        Schema::table('products', fn (Blueprint $t) => $t->dropConstrainedForeignId('collection_id'));
        Schema::dropIfExists('collections');
    }
};
