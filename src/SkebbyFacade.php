<?php

namespace alagaccia\skebby;

use Illuminate\Support\Facades\Facade;

/**
 * Skebby SMS Facade
 * 
 * This facade provides static access to the Skebby SMS service.
 * 
 * @method static array login() Authenticate with the Skebby API
 * @method static array|null send(string $phone, string $message, string|null $messageType = null) Send an SMS message
 * @method static array|null getInfo() Get account information and SMS credits status
 * @method static int getRemaining(string|null $messageType = null) Get remaining SMS credits for a message type
 * @method static array getAllRemainingCredits() Get remaining credits for all message types
 * @method static void clearAuthCache() Clear the authentication cache
 * 
 * @see \alagaccia\skebby\Skebby
 */
class SkebbyFacade extends Facade
{
    /**
     * Get the registered name of the component
     *
     * @return string The service container binding key
     */
    protected static function getFacadeAccessor(): string
    {
        return 'skebby';
    }
}