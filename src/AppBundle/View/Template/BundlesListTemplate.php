<?php

/**
 * Form OrderType for sorting Bundle ContentType on Bundle_list.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace AppBundle\View\Template;

use Pagerfanta\View\Template\DefaultTemplate;

class BundlesListTemplate extends DefaultTemplate
{
    public function __construct()
    {
        parent::__construct();

        $this->setOptions([
            'container_template' => '<div class="pagination"><nav>%pages%</nav></div>',
            'prev_message' => '<i class="fa fa-chevron-left"></i>',
            'next_message' => '<i class="fa fa-chevron-right"></i>',
        ]);
    }
}
