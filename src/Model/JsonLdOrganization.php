<?php
declare(strict_types=1);

namespace Themes\AbstractBlogTheme\Model;

use JMS\Serializer\Annotation as JMS;

class JsonLdOrganization extends JsonLdObject
{
    /**
     * @var string
     * @JMS\SerializedName("@type")
     */
    public static $type = "Organization";

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $logo;

    /**
     * AmpPerson constructor.
     *
     * @param string $name
     * @param string $logo
     */
    public function __construct($name, $logo = '')
    {
        $this->name = $name;
        $this->logo = $logo;
    }
}
