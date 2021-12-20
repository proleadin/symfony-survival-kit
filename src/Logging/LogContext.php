<?php declare(strict_types=1);

namespace Leadin\SurvivalKitBundle\Logging;

use MyCLabs\Enum\Enum;

/**
 * @method static self DEFAULT()
 * @method static self MESSAGE()
 * @method static self TRACKING()
 * @method static self SECURITY()
 * 
 * Lists possible log contexts using private constants.
 * Can be extended if the service requires additional contexts.
 */
class LogContext extends Enum
{
    protected const DEFAULT  = 'default';
    protected const MESSAGE  = 'message';
    protected const TRACKING = 'tracking';
    protected const SECURITY = 'security';
}
