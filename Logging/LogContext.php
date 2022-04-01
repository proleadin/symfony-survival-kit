<?php declare(strict_types=1);

namespace Leadin\SurvivalKitBundle\Logging;

use MyCLabs\Enum\Enum;

/**
 * @method static self DEFAULT()
 * @method static self MESSAGE()
 * @method static self TRACKING()
 * @method static self SECURITY()
 * @method static self DEPLOYMENT()
 * @method static self LONG_DEBUG()
 * @method static self SSK_BUNDLE()
 * @method static self INTERNAL_TOOLS()
 * 
 * Lists possible log contexts using private constants.
 * Can be extended if the service requires additional contexts.
 */
class LogContext extends Enum
{
    protected const DEFAULT        = 'default';
    protected const MESSAGE        = 'message';
    protected const TRACKING       = 'tracking';
    protected const SECURITY       = 'security';
    protected const DEPLOYMENT     = 'deployment';
    protected const LONG_DEBUG     = 'long_debug';
    protected const SSK_BUNDLE     = 'ssk_bundle';
    protected const INTERNAL_TOOLS = 'internal_tools';
}
