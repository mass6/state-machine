<?php

namespace Acme;

class TicketStateTransition extends \Eloquent
{

    public $statefulModel;
    // Set the stateful attribute prefix.
    // Useful for polymorphic association, when it's different than the table name of the stateful Model.
    // E.g. 'my_stateful_model_id' and 'my_stateful_model_type' attributes become 'imageable_id' and 'imageable_type'.
    // public $statefulName = 'imageable';
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public static function boot()
    {

        parent::boot();
        // Pro tips:
        static::creating(function ($model) {
            // You can save fields/additional attributes in your database here:
            $model->user_id = $model->statefulModel->user_id;
            $model->company = 'Acme Trading';
        });
    }

}
