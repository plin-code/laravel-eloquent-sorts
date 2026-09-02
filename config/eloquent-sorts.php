<?php

declare(strict_types=1);

return [

    /*
     | Register the orderByRelation, orderByRelationCount and orderByEnum
     | macros on Illuminate\Database\Eloquent\Builder.
     |
     | Turning this off leaves the Orders\ and Sorts\ classes fully working:
     | only the macros disappear.
     */
    'register_macros' => env('ELOQUENT_SORTS_REGISTER_MACROS', true),

];
