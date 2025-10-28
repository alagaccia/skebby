<?php

namespace alagaccia\skebby;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use alagaccia\skebby\Exceptions\SkebbyException;

/**
 * Skebby SMS API Client
 * 
 * This class provides an interface to interact with the Skebby SMS API
 * for sending SMS messages and retrieving account information.
 */
class Skebby
{
    /**
     * @var string|null The Skebby username
     */
    protected ?string $username;

    /**
     * @var string|null The Skebby password
     */
    protected ?string $password;

    /**
     * @var string|null The SMS sender alias
     */
    protected ?string $alias;

    /**
     * @var string The SMS quality/type
     */
    protected string $quality;

    /**
     * @var array|null Cached authentication credentials
     */
    protected ?array $authCache = null;

    /**
     * Skebby API base URL
     */
    const SKEBBY_BASEURL = 'https://api.skebby.it/API/v1.0/REST/';

    /**
     * SMS Message Types
     */
    const MESSAGE_CLASSIC_PLUS = 'GP';
    const MESSAGE_CLASSIC = 'TI';
    const MESSAGE_BASIC = 'SI';
    const MESSAGE_EXPORT = 'EE';
    const MESSAGE_ADVERTISING = 'AD';

    /**
     * Available SMS quality types
     */
    const QUALITY_TYPES = [
        self::MESSAGE_CLASSIC_PLUS,
        self::MESSAGE_CLASSIC,
        self::MESSAGE_BASIC,
        self::MESSAGE_EXPORT,
        self::MESSAGE_ADVERTISING,
    ];

    /**
     * Initialize the Skebby SMS client with configuration values
     * 
     * @throws \InvalidArgumentException If required configuration is missing
     */
    public function __construct()
    {
        $this->username = config('skebby.SKEBBY_USER') ?? env('SKEBBY_USER');
        $this->password = config('skebby.SKEBBY_PWD') ?? env('SKEBBY_PWD');
        $this->alias = config('skebby.SKEBBY_ALIAS') ?? env('SKEBBY_ALIAS');
        $this->quality = config('skebby.SKEBBY_QUALITY', self::MESSAGE_CLASSIC) ?? env('SKEBBY_QUALITY', self::MESSAGE_CLASSIC);

        // Validate required configuration
        if (empty($this->username) || empty($this->password)) {
            throw SkebbyException::validationFailed('Skebby username and password are required. Please check your configuration.');
        }

        // Validate quality type
        if (!in_array($this->quality, self::QUALITY_TYPES)) {
            throw SkebbyException::validationFailed('Invalid SMS quality type. Must be one of: ' . implode(', ', self::QUALITY_TYPES));
        }
    }

    /**
     * Authenticate with the Skebby API and retrieve session credentials
     * 
     * This method authenticates the user with the provided credentials
     * and returns an array containing [user_key, session_key]
     * 
     * @return array Array containing user_key and session_key
     * @throws SkebbyException If authentication fails or API request fails
     */
    public function login(): array
    {
        // Return cached auth if available
        if ($this->authCache !== null) {
            return $this->authCache;
        }

        try {
            $response = Http::timeout(30)->get(self::SKEBBY_BASEURL . 'login', [
                'username' => $this->username,
                'password' => $this->password
            ]);

            if (!$response->successful()) {
                throw SkebbyException::authenticationFailed('Authentication failed: HTTP ' . $response->status());
            }

            $responseBody = trim($response->body());
            
            if (empty($responseBody)) {
                throw SkebbyException::authenticationFailed('Empty response from authentication server');
            }

            $auth = explode(';', $responseBody);

            if (count($auth) !== 2 || empty($auth[0]) || empty($auth[1])) {
                throw SkebbyException::authenticationFailed('Invalid authentication response format');
            }

            // Cache the authentication for this request
            $this->authCache = $auth;

            return $auth;
        } catch (SkebbyException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw SkebbyException::authenticationFailed('Login failed: ' . $e->getMessage());
        }
    }

    /**
     * Send an SMS message to the specified phone number
     * 
     * This method sends an SMS message using the configured Skebby account.
     * It automatically handles authentication and returns the API response.
     * 
     * @param string $phone The recipient phone number (international format recommended)
     * @param string $message The SMS message content (max 160 chars for standard SMS)
     * @param string|null $messageType Optional message type override
     * @return array|null The API response data or null on failure
     * @throws SkebbyException If input parameters are invalid or API request fails
     */
    public function send(string $phone, string $message, ?string $messageType = null): ?array
    {
        // Validate input parameters
        if (empty($phone)) {
            throw SkebbyException::validationFailed('Phone number cannot be empty');
        }

        if (empty($message)) {
            throw SkebbyException::validationFailed('Message cannot be empty');
        }

        if (strlen($message) > 1600) { // Conservative limit for concatenated SMS
            throw SkebbyException::validationFailed('Message is too long (max 1600 characters)');
        }

        // Use provided message type or default quality
        $messageType = $messageType ?? $this->quality;
        
        if (!in_array($messageType, self::QUALITY_TYPES)) {
            throw SkebbyException::validationFailed('Invalid message type: ' . $messageType);
        }

        try {
            $auth = $this->login();
            
            $body = [
                'message' => $message,
                'message_type' => $messageType,
                'returnRemaining' => true,
                'recipient' => [$phone],
                'sender' => $this->alias,
            ];

            $response = Http::timeout(30)->withHeaders([
                'Content-Type' => 'application/json',
                'user_key' => $auth[0],
                'Session_key' => $auth[1]
            ])->post(self::SKEBBY_BASEURL . 'sms', $body);

            if (!$response->successful()) {
                throw SkebbyException::apiRequestFailed('SMS sending failed: HTTP ' . $response->status() . ' - ' . $response->body(), $response->status());
            }

            return $response->json();
        } catch (SkebbyException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw SkebbyException::apiRequestFailed('SMS sending failed: ' . $e->getMessage());
        }
    }

    /**
     * Get account information and SMS credits status
     * 
     * This method retrieves detailed information about the Skebby account,
     * including available SMS credits for different message types.
     * 
     * @return array|null The account information data or null on failure
     * @throws SkebbyException If the API request fails
     */
    public function getInfo(): ?array
    {
        try {
            $auth = $this->login();
            
            $response = Http::timeout(30)->withHeaders([
                'Content-Type' => 'application/json',
                'user_key' => $auth[0],
                'Session_key' => $auth[1]
            ])->get(self::SKEBBY_BASEURL . 'status');

            if (!$response->successful()) {
                throw SkebbyException::apiRequestFailed('Failed to retrieve account info: HTTP ' . $response->status() . ' - ' . $response->body(), $response->status());
            }

            return $response->json();
        } catch (SkebbyException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw SkebbyException::apiRequestFailed('Account info retrieval failed: ' . $e->getMessage());
        }
    }

    /**
     * Get remaining SMS credits for the current quality type
     * 
     * This method retrieves the remaining SMS credits for the configured
     * message quality type, or for a specific quality type if provided.
     * 
     * @param string|null $messageType Optional message type to check credits for
     * @return int The number of remaining SMS credits
     * @throws SkebbyException If account info cannot be retrieved or credits cannot be determined
     */
    public function getRemaining(?string $messageType = null): int
    {
        $info = $this->getInfo();
        
        if (!$info || !isset($info['sms'])) {
            throw SkebbyException::apiRequestFailed('Unable to retrieve SMS credit information');
        }

        $messageType = $messageType ?? $this->quality;
        
        // Map message types to their typical array indices
        $typeIndexMap = [
            self::MESSAGE_CLASSIC_PLUS => 0,
            self::MESSAGE_CLASSIC => 1,
            self::MESSAGE_BASIC => 2,
            self::MESSAGE_EXPORT => 3,
            self::MESSAGE_ADVERTISING => 4,
        ];

        if (!isset($typeIndexMap[$messageType])) {
            throw SkebbyException::validationFailed('Unknown message type: ' . $messageType);
        }

        $index = $typeIndexMap[$messageType];
        
        if (!isset($info['sms'][$index]['quantity'])) {
            throw SkebbyException::apiRequestFailed('Credit information not available for message type: ' . $messageType);
        }

        return (int) $info['sms'][$index]['quantity'];
    }

    /**
     * Get remaining credits for all available message types
     * 
     * @return array Associative array with message types as keys and credit counts as values
     * @throws SkebbyException If account info cannot be retrieved
     */
    public function getAllRemainingCredits(): array
    {
        $info = $this->getInfo();
        
        if (!$info || !isset($info['sms'])) {
            throw SkebbyException::apiRequestFailed('Unable to retrieve SMS credit information');
        }

        $credits = [];
        $typeIndexMap = [
            self::MESSAGE_CLASSIC_PLUS => 0,
            self::MESSAGE_CLASSIC => 1,
            self::MESSAGE_BASIC => 2,
            self::MESSAGE_EXPORT => 3,
            self::MESSAGE_ADVERTISING => 4,
        ];

        foreach ($typeIndexMap as $type => $index) {
            if (isset($info['sms'][$index]['quantity'])) {
                $credits[$type] = (int) $info['sms'][$index]['quantity'];
            }
        }

        return $credits;
    }

    /**
     * Clear the authentication cache
     * 
     * This method clears the cached authentication credentials,
     * forcing a fresh login on the next API call.
     * 
     * @return void
     */
    public function clearAuthCache(): void
    {
        $this->authCache = null;
    }
}
