<?php

namespace WebSlinger\MailerFactory;

use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use WebSlinger\MailerFactory\DependencyInjection\WebSlingerMailerFactoryExtension;

class WebSlingerMailerFactoryBundle extends Bundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new WebSlingerMailerFactoryExtension();
    }
}
