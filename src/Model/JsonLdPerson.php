<?php
/**
 * thehopegallery.com - AmpArticle.php
 *
 * Initial version by: ambroisemaupate
 * Initial version created on: 15/10/2018
 */

namespace Themes\AbstractBlogTheme\Model;

use JMS\Serializer\Annotation as JMS;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Utils\UrlGenerators\DocumentUrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class JsonLdPerson
{
    /**
     * @JMS\SerializedName("@context")
     * @var string
     */
    public static $context = "http://schema.org";

    /**
     * @var string
     * @JMS\SerializedName("@type")
     */
    public static $type = "Person";

    /**
     * @var string
     */
    public $name;

    /**
     * AmpPerson constructor.
     *
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }
}
