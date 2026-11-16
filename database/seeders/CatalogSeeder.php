<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use App\Models\MenuItemStock;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $chicken = $this->tenant('kedai-ayam-rasa', 'Kedai Ayam Rasa', 'Nasi & ayam', 'Nasi dan lauk ayam berbumbu rumahan, disajikan hangat dengan sambal.');
            $this->menu($chicken, 'nasi-ayam-sambal', 'Nasi Ayam Sambal', 'Ayam goreng renyah dengan sambal merah, nasi putih, dan lalapan.', 22000, 24);
            $this->menu($chicken, 'ayam-bakar-madu', 'Ayam Bakar Madu', 'Ayam bakar oles madu, nasi putih, dan sambal tomat.', 26000, 15);

            $bakso = $this->tenant('mangkuk-sore', 'Mangkuk Sore', 'Bakso & mi', 'Bakso kuah dan mi ayam dengan topping segar.');
            $this->menu($bakso, 'bakso-urat', 'Bakso Urat', 'Bakso urat dengan mi, kuah kaldu, dan bawang goreng.', 22000, 18);
            $this->menu($bakso, 'mi-ayam-pangsit', 'Mi Ayam Pangsit', 'Mi ayam dengan sawi, ayam kecap, dan pangsit goreng.', 19000, 0);
        });
    }

    private function tenant(string $slug, string $name, string $category, string $description): Tenant
    {
        $tenant = Tenant::withTrashed()->firstOrCreate(['slug' => $slug], [
            'name' => $name,
            'category' => $category,
            'description' => $description,
            'photo_path' => 'images/catalog/'.$slug.'.webp',
        ]);

        if (! $tenant->trashed() && $tenant->category === null && $tenant->photo_path === null) {
            $tenant->update([
                'category' => $category,
                'description' => $tenant->description ?? $description,
                'photo_path' => 'images/catalog/'.$slug.'.webp',
            ]);
        }

        return $tenant;
    }

    private function menu(Tenant $tenant, string $slug, string $name, string $description, int $price, int $stock): void
    {
        if ($tenant->trashed()) {
            return;
        }

        $item = $tenant->menuItems()->withTrashed()->firstOrCreate(['slug' => $slug], [
            'name' => $name,
            'description' => $description,
            'price' => $price,
            'photo_path' => 'images/catalog/'.$slug.'.webp',
        ]);

        if (! $item->trashed()) {
            MenuItemStock::query()->firstOrCreate(
                ['menu_item_id' => $item->id, 'stock_date' => MenuItem::stockDate()],
                ['remaining' => $stock],
            );
        }
    }
}
