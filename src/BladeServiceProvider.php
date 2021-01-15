<?php

namespace ABetter\Mockup;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class BladeServiceProvider extends ServiceProvider {

    public function boot() {

		// Lipsum
        Blade::directive('lipsum', function($expression){
			return "<?php echo _lipsum($expression); ?>";
        });

		// Pixsum
        Blade::directive('pixsum', function($expression){
			return "<?php echo _pixsum($expression); ?>";
        });

    }

    public function register() {
        //
    }

}
