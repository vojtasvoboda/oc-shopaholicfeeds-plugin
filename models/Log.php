<?php namespace VojtaSvoboda\ShopaholicFeeds\Models;

use Input;
use Model;
use October\Rain\Argon\Argon;
use Request;

/**
 * Log Model
 */
class Log extends Model
{
    /**
     * @var string $table The database table used by the model.
     */
    public $table = 'vojtasvoboda_shopaholicfeeds_feed_logs';

    /**
     * @var array $fillable The attributes that are mass assignable.
     */
    protected $fillable = ['allowed'];

    /**
     * @var array $dates Attributes to be cast to Argon (Carbon) instances.
     */
    protected $dates = ['created_at'];

    /**
     * @var array $jsonable List of attribute names which are json encoded and decoded from the database.
     */
    protected $jsonable = ['params'];

    /**
     * @var array $belongsTo Relations.
     */
    public $belongsTo = [
        'feed' => [Feed::class],
    ];

    /**
     * @var bool $timestamps If save timestamps.
     */
    public $timestamps = false;

    /**
     * Before create event handler.
     */
    public function beforeCreate()
    {
        $this->remote_addr = Request::ip();
        $this->uri = substr(Request::server('REQUEST_URI'), 0, 600);
        $this->method = substr(Request::server('REQUEST_METHOD'), 0, 20);
        $this->params = Input::all();
        $this->created_at = Argon::now();

        if ($this->allowed === false) {
            $this->took_seconds = round(microtime(true) - LARAVEL_START, 2);
        }
    }

    /**
     * Log time needed for export the feed.
     */
    public function logTime()
    {
        $this->took_seconds = round(microtime(true) - LARAVEL_START, 2);
        $this->save();
    }
}
