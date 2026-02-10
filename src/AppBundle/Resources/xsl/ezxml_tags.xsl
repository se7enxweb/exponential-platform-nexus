<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet
        version="1.0"
        xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
        xmlns:xhtml="http://ez.no/namespaces/ezpublish3/xhtml/"
        xmlns:custom="http://ez.no/namespaces/ezpublish3/custom/"
        xmlns:image="http://ez.no/namespaces/ezpublish3/image/"
        exclude-result-prefixes="xhtml custom image">

    <xsl:output method="html" indent="yes" encoding="UTF-8" />

    <!-- Place your custom tags here -->

    <xsl:template match="custom[@name='trennlinie']">
        <hr><xsl:apply-templates/></hr>
    </xsl:template>

    <xsl:template match="custom[@name='Umfliessen_beenden']">
        <div style="clear: both; height: 1px;"></div>
    </xsl:template>

    <xsl:template match="custom[@name='Ausklappbarer_Abschnitt_mobil']">
        <div class="d-none d-lg-block">
            <xsl:apply-templates />
        </div>

        <div class="toggle-block state-closed close-others has-custom-title mobil d-lg-none">
            <div>
                <xsl:attribute name="class">content</xsl:attribute>
                <xsl:apply-templates />

                <div style="clear: both; height: 1px;"></div>
            </div>

            <div class="control">
                <span class="text-is-closed">
                    <xsl:value-of select='@custom:Name'/>
                </span>

                <span class="text-is-opened">
                    <xsl:value-of select='@custom:Name'/>
                </span>
            </div>
        </div>
    </xsl:template>

    <xsl:template match="custom[@name='Ausklappbarer_Textabschnitt']">
        <span class="d-none">
            <xsl:apply-templates />
        </span>

        <span class="toggle-block state-closed close-others has-custom-title section">
            <span>
                <xsl:attribute name="class">content</xsl:attribute>
                <xsl:apply-templates />
            </span>

            <span class="control-section">
                <span class="text-is-closed">
                    <xsl:value-of select='@custom:Name'/>
                </span>

                <span class="text-is-opened">
                    <xsl:value-of select='@custom:Name'/>
                </span>
            </span>
        </span>
    </xsl:template>

</xsl:stylesheet>
