<?php /* #?ini charset="utf-8"?

[ImageMagick]
IsEnabled=true
ExecutablePath=/usr/bin
Executable=convert
PreParameters=+profile "*"

[AliasSettings]
AliasList[]=social_network_image

# The image variation of the SEO bundle (se7enxweb/ezseobundle, variations.yml): its meta name schema asks
# for it when content is published, and the image variations of this project are made by the legacy
# kernel (site-legacy-bundle), which logged "No such group social_network_image in ini file image.ini"
# on every publish and upload. The SEO bundle only strips and upscales small images; here it is the
# original image without its metadata.
[social_network_image]
Reference=
Filters[]

[MIMETypeSettings]
Quality[]
Quality[]=image/jpeg;90
*/ ?>
