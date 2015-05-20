# Grandiloquent (A Laravel 5 Eloquent Package)

Grandiloquent is a simple and elegant extension on Eloquent that makes mass writes to the database more efficient. 
My entire philosophy was to ensure that Eloquent methods, such as saveMany, and push, would be more efficient on the database. 

## Installation

Installation can be done by adding the following line to your composer.json require:

    "jamesspence/grandiloquent": "1.*"
    
##Configuration

You'll need to either A) extend the GrandModel class, or add the GrandModelTrait to your Eloquent classes.
The idea is to make this configuration as simple as possible.

## Basic Usage

Grandiloquent will integrate directly into current workflow. For example, let's assume you have a model system defined thus:

```php
<?php

use Grandiloquent/GrandModel;

class Book extends GrandModel
{
    public function chapters()
    {
        return $this->hasMany(Chapter::class);
    }
     
}
```

And then your Chapter model:
```php
<?php

use Grandiloquent/GrandModel;

class Chapter extends GrandModel
{
    public function book()
    {
        return $this->belongsTo(Book::class);
    }
}
```

Using Grandiloquent, in order to save an array of models directly to their parent (in this case the Book), 
you can call the same function as you would with Eloquent:

```php
$book = Book::find(1);
$chapters = [];
for($i = 0; $i < 5; ++$i)
{
    $chapter = new Chapter();
    $chapter->name = "Chapter $i";
    $chapters[] = $chapter;
}
$book->chapters()->saveMany($chapters);
```

This will work in an equivalent manner to Eloquent's saveMany, but will do it in, at most, three queries.
This extension also modifies default push behavior. Rather than pushing each model individually, pushMany (to a degree)
combines the individual queries into larger queries of related models.