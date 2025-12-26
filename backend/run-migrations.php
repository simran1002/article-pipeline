<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;

$capsule = new Capsule;

$capsule->addConnection([
    'driver' => 'sqlite',
    'database' => __DIR__ . '/database/database.sqlite',
    'prefix' => '',
]);

$capsule->setEventDispatcher(new Dispatcher(new Container));
$capsule->setAsGlobal();
$capsule->bootEloquent();

// Create articles table
try {
    Capsule::schema()->dropIfExists('articles');
    
    Capsule::schema()->create('articles', function ($table) {
        $table->id();
        $table->string('title');
        $table->text('content');
        $table->string('slug')->unique();
        $table->string('original_url')->nullable();
        $table->text('excerpt')->nullable();
        $table->string('author')->nullable();
        $table->timestamp('published_at')->nullable();
        $table->boolean('is_updated')->default(false);
        $table->text('reference_articles')->nullable();
        $table->timestamps();
    });
    
    echo "✓ Database table 'articles' created successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

