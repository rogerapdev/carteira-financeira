<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->uuid('public_id')->after('id')->unique()
                ->comment('Identificador público seguro para uso em APIs');
            $table->index('public_id');
        });
        
        // Preenche os public_ids para registros existentes
        $this->preencherPublicIds();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropIndex(['public_id']);
            $table->dropColumn('public_id');
        });
    }
    
    /**
     * Preenche os campos public_id para registros existentes
     */
    private function preencherPublicIds(): void
    {
        DB::table('accounts')->orderBy('id')->each(function ($conta) {
            DB::table('accounts')
                ->where('id', $conta->id)
                ->update(['public_id' => Str::uuid()]);
        });
    }
};
