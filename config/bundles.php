<?php

return [
    // Symfony
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Symfony\Bundle\SecurityBundle\SecurityBundle::class => ['all' => true],
    Symfony\Bundle\TwigBundle\TwigBundle::class => ['all' => true],
    Symfony\Bundle\MonologBundle\MonologBundle::class => ['all' => true],
    Symfony\Bundle\WebProfilerBundle\WebProfilerBundle::class => ['dev' => true, 'test' => true],
    Symfony\Bundle\DebugBundle\DebugBundle::class => ['dev' => true],
    Symfony\Bundle\MakerBundle\MakerBundle::class => ['dev' => true],
    Symfony\WebpackEncoreBundle\WebpackEncoreBundle::class => ['all' => true],
    Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle::class => ['all' => true],

    // Doctrine
    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class => ['all' => true],
    Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle::class => ['all' => true],

    // Third-party
    Bazinga\Bundle\JsTranslationBundle\BazingaJsTranslationBundle::class => ['all' => true],
    FOS\JsRoutingBundle\FOSJsRoutingBundle::class => ['all' => true],
    FOS\HttpCacheBundle\FOSHttpCacheBundle::class => ['all' => true],
    JMS\TranslationBundle\JMSTranslationBundle::class => ['all' => true],
    Liip\ImagineBundle\LiipImagineBundle::class => ['all' => true],
    Nelmio\CorsBundle\NelmioCorsBundle::class => ['all' => true],
    Oneup\FlysystemBundle\OneupFlysystemBundle::class => ['all' => true],
    Knp\Bundle\MenuBundle\KnpMenuBundle::class => ['all' => true],
    Twig\Extra\TwigExtraBundle\TwigExtraBundle::class => ['all' => true],
    BabDev\PagerfantaBundle\BabDevPagerfantaBundle::class => ['all' => true],
    Hautelook\TemplatedUriBundle\HautelookTemplatedUriBundle::class => ['all' => true],
    Lexik\Bundle\JWTAuthenticationBundle\LexikJWTAuthenticationBundle::class => ['all' => true],
    Overblog\GraphQLBundle\OverblogGraphQLBundle::class => ['all' => true],
    Overblog\GraphiQLBundle\OverblogGraphiQLBundle::class => ['dev' => true],
    Sentry\SentryBundle\SentryBundle::class => ['all' => true],
    Kaliop\eZMigrationBundle\EzMigrationBundle::class => ['all' => true],

    // eZ Platform 3.3 — kernel (ibexa/core ~4.6 replacing ezsystems/ezplatform-kernel ~1.3)
    EzSystems\EzPlatformCoreBundle\EzPlatformCoreBundle::class => ['all' => true],
    Ibexa\Bundle\Core\IbexaCoreBundle::class => ['all' => true],
    Ibexa\Bundle\LegacySearchEngine\IbexaLegacySearchEngineBundle::class => ['all' => true],
    Ibexa\Bundle\IO\IbexaIOBundle::class => ['all' => true],
    Ibexa\Bundle\Debug\IbexaDebugBundle::class => ['dev' => true, 'test' => true, 'behat' => true],
    Ibexa\Bundle\DoctrineSchema\DoctrineSchemaBundle::class => ['all' => true],
    Ibexa\Bundle\RepositoryInstaller\IbexaRepositoryInstallerBundle::class => ['all' => true],

    // eZ Platform 3.3 — separate packages
    Ibexa\Bundle\HttpCache\IbexaHttpCacheBundle::class => ['all' => true],
    Ibexa\Bundle\Rest\IbexaRestBundle::class => ['all' => true],
    EzSystems\EzPlatformSolrSearchEngineBundle\EzSystemsEzPlatformSolrSearchEngineBundle::class => ['all' => true],
    EzSystems\EzSupportToolsBundle\EzSystemsEzSupportToolsBundle::class => ['all' => true],
    EzSystems\EzPlatformCronBundle\EzPlatformCronBundle::class => ['all' => true],
    EzSystems\EzPlatformDesignEngineBundle\EzPlatformDesignEngineBundle::class => ['all' => true],
    EzSystems\EzPlatformStandardDesignBundle\EzPlatformStandardDesignBundle::class => ['all' => true],
    Ibexa\Bundle\FieldTypeRichText\IbexaFieldTypeRichTextBundle::class => ['all' => true],
    Ibexa\Bundle\ContentForms\IbexaContentFormsBundle::class => ['all' => true],
    EzSystems\EzPlatformAdminUiBundle\EzPlatformAdminUiBundle::class => ['all' => true],
    EzSystems\EzPlatformAdminUiAssetsBundle\EzPlatformAdminUiAssetsBundle::class => ['all' => true],
    EzSystems\EzPlatformUserBundle\EzPlatformUserBundle::class => ['all' => true],
    EzSystems\EzPlatformMatrixFieldtypeBundle\EzPlatformMatrixFieldtypeBundle::class => ['all' => true],
    EzSystems\EzPlatformGraphQL\EzSystemsEzPlatformGraphQLBundle::class => ['all' => true],
    EzSystems\EzPlatformQueryFieldType\Symfony\EzSystemsEzPlatformQueryFieldTypeBundle::class => ['all' => true],
    Ibexa\Platform\Bundle\Search\IbexaPlatformSearchBundle::class => ['all' => true],

    // eZ Platform XmlText fieldtype
    EzSystems\EzPlatformXmlTextFieldTypeBundle\EzSystemsEzPlatformXmlTextFieldTypeBundle::class => ['all' => true],

    // eZ Publish Legacy bridge
    eZ\Bundle\EzPublishLegacyBundle\EzPublishLegacyBundle::class => ['all' => true],

    // se7enxweb / EzCoreExtra
    Lolautruche\EzCoreExtraBundle\EzCoreExtraBundle::class => ['all' => true],
    MediataCom\MediataEzpageFieldtypeBundle\MediataEzpageFieldtypeBundle::class => ['all' => true],

    // Netgen Content Browser
    Netgen\Bundle\ContentBrowserBundle\NetgenContentBrowserBundle::class => ['all' => true],
    Netgen\Bundle\ContentBrowserUIBundle\NetgenContentBrowserUIBundle::class => ['all' => true],
    Netgen\Bundle\ContentBrowserEzPlatformBundle\NetgenContentBrowserEzPlatformBundle::class => ['all' => true],

    // Netgen Layouts 1.4 (ezplatform)
    Netgen\Bundle\LayoutsBundle\NetgenLayoutsBundle::class => ['all' => true],
    Netgen\Bundle\LayoutsAdminBundle\NetgenLayoutsAdminBundle::class => ['all' => true],
    Netgen\Bundle\LayoutsUIBundle\NetgenLayoutsUIBundle::class => ['all' => true],
    Netgen\Bundle\LayoutsDebugBundle\NetgenLayoutsDebugBundle::class => ['dev' => true, 'test' => true],
    Netgen\Bundle\LayoutsStandardBundle\NetgenLayoutsStandardBundle::class => ['all' => true],
    Netgen\Bundle\LayoutsEzPlatformBundle\NetgenLayoutsEzPlatformBundle::class => ['all' => true],
    Netgen\Bundle\LayoutsEzPlatformSiteApiBundle\NetgenLayoutsEzPlatformSiteApiBundle::class => ['all' => true],
    Netgen\Bundle\LayoutsEzPlatformRelationListQueryBundle\NetgenLayoutsEzPlatformRelationListQueryBundle::class => ['all' => true],
    Netgen\Bundle\LayoutsEzPlatformTagsQueryBundle\NetgenLayoutsEzPlatformTagsQueryBundle::class => ['all' => true],

    // Netgen Site API / Site bundle
    Netgen\Bundle\IbexaSiteApiBundle\NetgenIbexaSiteApiBundle::class => ['all' => true],
    Netgen\Bundle\EzFormsBundle\NetgenEzFormsBundle::class => ['all' => true],
    Netgen\Bundle\EzPlatformSearchExtraBundle\NetgenEzPlatformSearchExtraBundle::class => ['all' => true],
    Netgen\Bundle\InformationCollectionBundle\NetgenInformationCollectionBundle::class => ['all' => true],
    Netgen\Bundle\SiteInstallerBundle\NetgenSiteInstallerBundle::class => ['all' => true],
    Netgen\Bundle\SiteBundle\NetgenSiteBundle::class => ['all' => true],
    Netgen\Bundle\SiteAccessRoutesBundle\NetgenSiteAccessRoutesBundle::class => ['all' => true],
    Netgen\Bundle\OpenGraphBundle\NetgenOpenGraphBundle::class => ['all' => true],
    Netgen\Bundle\ContentTypeListBundle\NetgenContentTypeListBundle::class => ['all' => true],
    Netgen\Bundle\BirthdayBundle\NetgenBirthdayBundle::class => ['all' => true],
    Netgen\Bundle\EnhancedSelectionBundle\NetgenEnhancedSelectionBundle::class => ['all' => true],
    Netgen\Bundle\MetadataBundle\NetgenMetadataBundle::class => ['all' => true],

    // Netgen Admin UI / Legacy
    Netgen\Bundle\AdminUIBundle\NetgenAdminUIBundle::class => ['all' => true],
    Netgen\Bundle\SiteLegacyBundle\NetgenSiteLegacyBundle::class => ['all' => true],

    // Tags
    Netgen\TagsBundle\NetgenTagsBundle::class => ['all' => true],
];
