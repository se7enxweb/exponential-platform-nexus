# 5. The demo site and Netgen Layouts

This chapter explains what the installer put into your database and how the pieces work together, so that you can
change the demo into your own site instead of guessing. It describes the two demo data sets, the content tree of the
Netgen Media Site demo (Fit & Healthy and Bold Agency) as it stands in a working 1.3.0.x installation, its
siteaccesses, designs and languages, and its content types. It then explains Netgen Layouts: its concepts, the layout
types and zones, the layouts and mapping rules of the demo, shared layouts and linked zones, blocks and collections,
and the Layouts administration. It ends with Netgen Tags, the Netgen Site API and how the demo uses it, the CJW demo
of the 2.5 line, and how to take the demo apart.

[Contents](README.md) · Previous: [4. Installing](04-installing.md) · Next: [6. Serving the site](06-serving-the-site.md)

## 5.1 Two demo data sets

| Data set | Lines | Installer type | Languages | Designs |
|---|---|---|---|---|
| **Netgen Media Site demo**: two sites in one repository, *Fit & Healthy* (a health and fitness magazine) and *Bold Agency* (an agency site) | 1.1.0.x, 1.2.0.x, 1.3.0.x (`exponential-media`, or `netgen-media` on MySQL); 1.0.0.x (`netgen-media`, the 1.8 version of the demo) | `exponential-media`, `netgen-media` | eng-GB, ger-DE | `fh`, `bold`, sharing `app`, `common`, `standard` |
| **CJW demo** ("JAC Example") | 2.5 (`cjw-exponential-media`), 1.0.0.x (SQL dumps, `exponential-cjw` on SQLite) | `cjw-exponential-media`, `exponential-cjw` | ger-DE, eng-GB | `cjw_app` |

The figures and lists in sections 5.2 to 5.8 were read from the database of the 1.3.0.x reference installation
(`exponential-media` on SQLite). The 1.1.0.x and 1.2.0.x lines load the same demo from their own `data/` files; the
numbers can differ slightly between data versions. Section [5.10](#510-the-cjw-demo-of-the-25-line) covers the CJW demo.

## 5.2 The content tree

After the install the repository holds 290 published content items. The top of the tree:

```
Sites (location 2)
├── Fit & Healthy (385)              root of the fh_eng siteaccess
│   ├── Fitness (167)
│   ├── Healthy eating (168)
│   ├── Recipes (190)
│   ├── Running (195)
│   ├── Workout (210)
│   ├── Health (339)
│   ├── HIIT (353)
│   ├── Video (198)
│   ├── Contact (224)
│   ├── Privacy Policy (506), Cookie Policy (507)
│   ├── Authors (225)
│   └── Showcase (357)
└── Bold Agency (386)                root of the bold_eng and bold_ger siteaccesses
    ├── Services (387)
    ├── About Us (392)
    ├── Careers (393)
    ├── Contact Us (405)
    └── Privacy Policy (508), Cookie Policy (509)
Media (43)
├── Menu items, Components, Clients & Partners, Videos, Forms
├── Images (51), Files (52), Multimedia (53)
└── Configuration (64), Banners (85)
Users (5)
├── Members, Administrator users, Anonymous users
└── CMS and Layouts editors, Simple CMS and Layouts editors, Simple CMS editors
```

The numbers are location IDs. They matter because the configuration refers to them: the siteaccesses take their root
from parameters, and the layout mappings of [5.6](#56-the-demo-layouts-and-their-mappings) point at locations. Where
the configuration needs them, `config/app/server/dev/app.yaml` (imported by `config/app/server/dev.yaml` when
`SERVER_ENVIRONMENT=dev`) sets:

```yaml
parameters:
    ngsite.fh_group.locations.site_info.id: 65
    ngsite.bold_group.locations.site_info.id: 442
    ngsite.fh_group.locations.tree_root.id: 385
    ngsite.bold_group.locations.tree_root.id: 386
    ngsite.fh_group.locations.showcase.id: 357
```

and the location IDs of the shared components (hero 407, quote 445, about 413, features 422, logos 420, lead 470).
If you load other content, these IDs are what you change. On the branches, `config/app/server/prod.yaml` (for
`SERVER_ENVIRONMENT=prod`) repeats the same IDs. One line differs: on 1.1.0.x both `dev/app.yaml` and `prod.yaml` set
`ngsite.bold_group.locations.tree_root.id: 2`, although the demo data of that line has Bold Agency at location 386
too; set 386 there ([chapter 4](04-installing.md#45-the-110x-line)).

A quick way to see that the IDs match your content is to ask for the root of each site through the console of
1.3.0.x:

```bash
php bin/console dbal:run-sql "SELECT node_id, path_identification_string FROM ibexa_content_tree WHERE node_id IN (385, 386)"
```

The reference installation answers `/fit_healthy` for 385 and `/bold_agency` for 386. On 1.1.0.x and 1.2.0.x the table is
`ezcontentobject_tree` ([chapter 7](07-databases.md#731-repository-tables-ez-up-to-120x-ibexa_-on-130x)).

The user groups in `Users` come with roles for three kinds of editors: full CMS and Layouts editors, simple CMS and
Layouts editors, and simple CMS editors who cannot touch layouts. Look at them in the administration (Admin, Roles)
before you create your own.

## 5.3 Siteaccesses, designs and languages

From `config/app/packages/ibexa_siteaccess.yaml` (`ezpublish_siteaccess.yaml` on 1.1.0.x):

| Siteaccess | Group | Design | Languages | Tree root |
|---|---|---|---|---|
| `fh_eng` (default) | `fh_group`, `frontend_group` | `fh` | eng-GB | 385 (Fit & Healthy) |
| `bold_eng` | `bold_group`, `frontend_group` | `bold` | eng-GB | 386 (Bold Agency) |
| `bold_ger` | `bold_group`, `frontend_group` | `bold` | ger-DE | 386 |
| `adminui` | `admin_group` | `ngadmin` (1.3.0.x) | eng-GB, ger-DE | the whole tree |
| `ngadminui`, `legacy_admin` (1.1.0.x, 1.2.0.x) | `ngadmin_group` | | | |

They are matched by `URIElement: 1`: the first element of the path names the siteaccess (`/bold_ger/...`), and a path
without a known first element goes to `fh_eng`. On a production site you usually switch to host matching
([chapter 8](08-configuration.md)); the dev server configuration of 1.3.0.x contains a commented-out `Map\Host` example.

A **design** is an ordered list of template directories, and a template is looked up in each in turn (the design
engine's "themes"):

```yaml
ibexa_design_engine:
    design_list:
        fh:   [fh, app, common, standard]
        bold: [bold, app, common, standard]
```

So `templates/themes/fh/` holds only what Fit & Healthy does differently, `templates/themes/app/` what both sites share,
and Twig templates refer to `@ibexadesign/...` without naming a theme. Netgen Layouts has its own design list
(`netgen_layouts.design_list.app: [app]`, used by `frontend_group`). [Chapter 9](09-frontend-and-themes.md) explains
building and overriding them.

## 5.4 Content types

The demo defines 35 content types. Apart from the platform's own (`file`, `image`, `user`, `user_group`), they carry the
`ng_` prefix of the Media Site:

| Kind | Content types |
|---|---|
| Structure | `ng_frontpage`, `ng_landing_page`, `ng_category`, `ng_topic`, `ng_container`, `ng_site_info`, `ng_menu_item`, `ng_shortcut` |
| Articles and media | `ng_article`, `ng_blog_post`, `ng_news`, `ng_recipe`, `ng_video`, `ng_audio`, `ng_gallery`, `ng_banner`, `ng_htmlbox` |
| People and jobs | `ng_person`, `ng_job_position` |
| Forms | `ng_contact_form`, `ng_lead_form`, `ng_job_application_form` |
| Components (content blocks placed by Layouts) | `ng_component_hero`, `ng_component_about`, `ng_component_features`, `ng_component_lead`, `ng_component_logos`, `ng_component_quote`, `ng_accordion_item`, `ng_logo` |
| Legal | `ng_cookie_policy` |

Forms are answered through Netgen Information Collection: a submitted contact form is stored and mailed using
`collected_info_sender` and `collected_info_recipient`, which the demo configuration sets to Netgen's example addresses.
Change them before the site sends mail: in `config/app/server/dev/app.yaml` for `SERVER_ENVIRONMENT=dev`, through
`COLLECTED_INFO_SENDER` and `COLLECTED_INFO_RECIPIENT` (1.2.0.x, 1.3.0.x) or `MAIL_FROM` and `MAIL_TO` (1.1.0.x) for
the `prod.yaml` server file (section 5.11).

## 5.5 Netgen Layouts: the concepts

Netgen Layouts separates **what a page shows** (content in the repository) from **how the page is arranged** (a
layout). Editors change the arrangement in a browser without touching templates. The vocabulary, from the
[Netgen Layouts documentation](https://docs.netgen.io/projects/layouts/en/latest/overview/main_concepts.html):

| Concept | What it is |
|---|---|
| **Layout** | the arrangement of a page: an instance of a layout type, holding blocks in its zones |
| **Layout type** | the blueprint of a layout: a Twig template and a list of zones |
| **Zone** | a named area of a layout (`header`, `main`, `pre_footer`, `footer`); its blocks render one after the other |
| **Shared layout** | a layout that is never mapped to a page; its zones are linked into other layouts |
| **Layout mapping (rule)** | "use this layout here": one or more **targets** (a location, a subtree, a URL prefix, a route) and optional **conditions** (a content type, a siteaccess), with a priority |
| **Layout resolver** | the part that walks the rules by priority on every request and picks the first that matches |
| **Block** | one unit on the page (a title, a list, a gallery, a component), made from a **block type** and configured with **parameters**; it has a **view type** (its template) |
| **Block definition** | the PHP class and configuration that define what a block can do and which parameters it has |
| **Container block** | a block that holds other blocks (one to four columns) |
| **Collection** | the items a block shows: **manual** (picked with the Content Browser) or **dynamic** (fetched by a **query type**), or both mixed |
| **Block Manager** | the drag-and-drop editor of one layout |
| **Layout Manager** | the list of layouts, shared layouts and mappings, plus roles and policies |

A request is rendered like this: the platform finds the content for the URL, the layout resolver picks a layout by
the rules, the layout's template renders its zones, and each block renders its items with the item's view type. The
content's own full view (its body text, images) is usually one block in the `main` zone, the `full_view` block.

## 5.6 The demo layouts and their mappings

### Layout types

The demo uses the layout types of `netgen/layouts-standard` (`layout_types.yaml`):

| Layout type | Zones |
|---|---|
| `layout_1` (Layout 1) | `main` |
| `layout_2` (Layout 2) | `header`, `post_header`, `main`, `pre_footer`, `footer` |
| `layout_3`, `layout_4`, `layout_5` | `header`, `post_header`, `left`, `right`, `pre_footer`, `footer` |
| `layout_6` | `header`, `post_header`, `top`, `left`, `center`, `right`, `pre_footer`, `footer` |

### Layouts

21 layouts are published:

| Layout | Type | Shared |
|---|---|---|
| Header / Footer | `layout_2` | yes |
| Prefooter | `layout_1` | yes |
| Default Layout, Default Layout With Container | `layout_2` | no |
| Full / Article, Full / Article (Plain), Full / Topic, Full / Video | `layout_2` | no |
| Full / Recipe | `layout_4` | no |
| Landing Page / Home, Landing Page / Fitness, Landing Page / Healthy Eating, Landing Page / Video, Landing Page (Topic) / Running | `layout_2` | no |
| Showcase | `layout_2` | no |
| Bold Agency - Home, - About Us, - Services, - Careers, - Contact Us, - Full / Job Position | `layout_2` | no |

### Shared layouts and linked zones

The page header and footer are not repeated in every layout. They live once in the shared layout **Header / Footer**,
and every page layout **links** its `header` and `footer` zones to the zones of the same name there (19 layouts do).
Two layouts link their `pre_footer` zone to the `main` zone of the shared **Prefooter** layout. Change the menu in the
header once, and every page shows the change.

In the Block Manager a linked zone shows the blocks of the shared layout but does not let you edit them there: open
the shared layout to edit them, or unlink the zone to give one layout its own blocks.

### Mapping rules: a worked example

18 rules map the layouts to pages. Listed by priority (the highest is tried first), with their targets and conditions:

| Priority | Layout | Target | Condition |
|---|---|---|---|
| 210 | Landing Page / Home | location 385 (Fit & Healthy) | |
| 200 | Landing Page / Fitness | location 167 | |
| 190 | Landing Page / Healthy Eating | location 168 | |
| 180 | Landing Page / Video | location 198 | |
| 170 | Landing Page (Topic) / Running | location 195 | |
| 160 | Full / Recipe | subtree 385 | content type `ng_recipe` |
| 150 | Full / Article (Plain) | locations 506, 507 | content type `ng_article`, `ng_blog_post`, `ng_news` |
| 130 | Full / Article | subtree 385 | content type `ng_article`, `ng_blog_post`, `ng_news` |
| 120 | Full / Topic | subtree 385 | content type `ng_topic` |
| 110 | Full / Video | subtrees 385 and 386 | content type `ng_video` |
| 100 | Showcase | location 357 | |
| 90 to 40 | Bold Agency - Home, About Us, Services, Careers, Contact Us | locations 386, 392, 387, 393, 405 | |
| 50 | Bold Agency - Full / Job Position | subtree 399 | content type `ng_job_position` |
| 10 | Default Layout | path prefix `/` | |

How a request is resolved, step by step. A visitor opens a recipe below *Recipes* (location 190, inside the Fit &
Healthy subtree 385):

1. Priority 210 to 170 target single locations; the recipe is none of them.
2. Priority 160 targets subtree 385 with the condition "content type `ng_recipe`": both match, so the page uses
   **Full / Recipe** (`layout_4`, with left and right columns).
3. The rules below are not consulted.

An article in the same subtree falls through to priority 130 (**Full / Article**), except the two legal pages 506 and
507, which priority 150 catches first and renders with the plain article layout. A page that no specific rule matches,
for example a search result page, ends at priority 10, the catch-all **Default Layout** for every path.

What can go wrong:

- **A rule with a lower priority than a broad rule never wins.** If you add a rule for one article at priority 5, the
  subtree rule at 130 catches the article first. Give specific rules a higher priority.
- **Rules point at location IDs.** If you rebuild the content tree, or import other content, the rules point at the
  wrong pages or at nothing. Re-point them in the Layout Manager.
- **A disabled rule is skipped.** All 18 demo rules are enabled; the Layout Manager can switch a rule off without
  deleting it.

### Blocks and collections

The published layouts hold 243 blocks (plus one hidden root block per zone). The most used definitions are `title`
(61), `column` (55), `list` (45), the component blocks (`ibexa_component_features` 15, `ibexa_component_about` 11,
`ibexa_component_lead` 9, `ibexa_component_hero` 8, `ibexa_component_logos` 4, `ibexa_component_quote` 2), `twig_block`
(10), `two_columns` (7), `gallery` (6), `full_view` (6), `rich_text`, `html_snippet` and `button`.

Their collections show both kinds:

- **Manual items**: 38 locations picked by hand with the Content Browser (a fixed teaser, a logo row).
- **Dynamic queries**: 29 `ibexa_content_search` queries (for example "the newest articles below Healthy eating") and
  9 `content_by_topic` queries, a query type of the Media Site bundle that lists content by topic.

`twig_block` blocks render a named Twig block from the page template; that is how a layout places parts of the full
view (the article body, the recipe ingredients) exactly where the editor wants them.

## 5.7 The Layouts administration

The Layouts administration is reached through the administration siteaccess, under the route prefix
`netgen_layouts.route_prefix` (`/nglayouts`):

| Line | Layout Manager | Block Manager |
|---|---|---|
| 1.1.0.x, 1.2.0.x, 1.3.0.x | `/adminui/nglayouts/admin` | opened from the Layout Manager (`/adminui/nglayouts/app/...`) |
| 2.5 generation | `/nglayouts/admin` on the host of the `admin` siteaccess | as left |

Log in as `admin` (or as a member of "CMS and Layouts editors"). Typical tasks:

1. **Change a page's arrangement.** Layouts, pick for example *Landing Page / Healthy Eating*, Edit. The Block Manager
   opens with the block types on the left, the layout in the middle and the selected block's parameters on the right.
   Drag a *List* block into `main`, choose a query (*Content search*, parent location *Recipes*, limit 6), choose a view
   type, then publish the layout. Editing always works on a draft: visitors see the change only after publishing, and
   discarding the draft throws it away. (The button labels differ slightly between Layouts 1.4 and 2.0.)
2. **Map a layout to a new section.** In the mappings list, add a mapping, choose the layout, add a target (a
   location, or a subtree with a content-type condition), set the priority and enable it.
3. **Edit the header or footer.** Open the shared layout *Header / Footer*. Every layout that links those zones changes with it.
4. **Translate.** A layout has a main locale (the demo's is `en_GB`) and can be translated; blocks with translatable
   parameters then hold one value per language, which is how `bold_ger` shows German block titles.
5. **Clear the Layouts cache** after changes that do not show: the Layout Manager has a cache action for layouts and
   blocks. HTTP caches in front of the site (Varnish) may need a purge as well ([chapter 10](10-operations.md)).
6. **Roles and policies** decide who may edit which layouts; the demo's editor groups use them.

The `nglayouts:*` console commands (for example `nglayouts:layout:add`, `nglayouts:block:add`) script the same
operations; `php bin/console list nglayouts` shows those of your line.

## 5.8 Netgen Tags

Netgen Tags adds a tree of keywords with its own field type (`eztags`) and administration. The demo has 31 tags, among
them *Body*, *Cooking*, *Deadlift*, *Fruits*, *Gluten-free*, *HIIT*, *Intermittent fasting*, *Keto diet*,
*Mediterranean diet*, *Running*, *Strength training*, *Vegan*, *Weight loss*, *Workout* and *Yoga*. Articles carry
tags, and the Media Site uses them twice:

- topic pages (`ng_topic`) list the content tagged with their topic, through the `content_by_topic` query in their
  layout;
- the tags query of `netgen/layouts-ibexa-tags-query` lets an editor build a list block from tags.

Tags are edited in the administration interface (the Tags section of the content tree, or the Tags tab). Renaming a
tag renames it everywhere; merging two tags moves their content to one. The command
`ngsite:content:tag-content` tags content in bulk.

## 5.9 Netgen Site API

The Site API is a layer between the repository and the front end. Where the repository API returns content with
every translation and field definitions to resolve, the Site API returns *the* content of the current siteaccess:
fields in the right language already, the location, its parent and children, relations, all as objects that templates
can walk. On the Media Site front end it is the **primary content view**:

```yaml
# config/app/prepends/ibexa/content_view.yaml (1.3.0.x)
system:
    frontend_group:
        ng_site_api:
            site_api_is_primary_content_view: true
            fallback_to_secondary_content_view: false
        ng_content_view:
            full:
                ng_category:
                    template: "@ibexadesign/content/views/full/ng_category.html.twig"
                    queries:
                        subtree:
                            query_type: "SiteAPI:Location/Subtree"
                            max_per_page: "@=fieldValue('page_limit').value > 0 ? fieldValue('page_limit').value : 12"
                    match:
                        Identifier\ContentType: ng_category
```

How to read it: a page of type `ng_category` is rendered with that template, and the template receives a ready
paginated query of the category's subtree, configured in YAML rather than written in a controller. The template then
uses the Site API's objects (`content.fields.title`, `location.children`). `ng_content_view` is the Site API's view
configuration; `content_view` (without the prefix) is the platform's own and is not used for the front end.

The site bundle also defines **named objects**, so templates can reach fixed places by name instead of by ID
(`homepage` is the siteaccess's tree root, `site_info` and `showcase` come from the parameters of
[5.2](#52-the-content-tree)). And Netgen Layouts and the Content Browser search through the Site API's filter adapter
(`netgen_layouts_ibexa_site_api.search_service_adapter: filter` in `config/packages/netgen_layouts.yaml`), which reads
the database directly rather than the search index.

What can go wrong: because `fallback_to_secondary_content_view` is `false`, a view rule written for the platform's
`content_view` is not used on the front end; when you add a content type, add its rules under `ng_content_view`.
With `fail_on_missing_field: "%kernel.debug%"`, a template that asks for a field the content type lacks throws in
`dev` and renders nothing in `prod`.

See the [Site API documentation](https://docs.netgen.io/projects/site-api/en/latest/) for the objects, query types and
configuration reference. The packages are `netgen/ezplatform-site-api` (2.5), `se7enxweb/ezplatform-site-api` (1.1.0.x)
and `netgen/ibexa-site-api` (1.2.0.x, 1.3.0.x).

## 5.10 The CJW demo of the 2.5 line

The 2.5 line (`master`) installs the CJW content instead, with `cjw-exponential-media`:

- two public siteaccesses, `de` (default) and `en`, with the design `cjw_app`, languages ger-DE and eng-GB, and the
  start page `index_page: /startseite`;
- the administration `admin`, the Netgen admin UI `ngadminui` and the legacy kernel's administration `legacy_admin`;
- Netgen Layouts 1.4 on the 2.5 platform (`se7enxweb/layouts-ezplatform`), plus the project's own
  `Cjw\NetgenLayoutsExtendedBundle`;
- the legacy extension `app` and the image storage in `src/AppBundle/ezpublish_legacy/`, linked into
  `ezpublish_legacy/` ([chapter 4](04-installing.md#step-4-the-symlinks-into-the-legacy-kernel)).

The root of the CJW site is location 168, "JAC Example" (`/1/2/168/`), and the start page `startseite` is a child of
it. Check that `ngsite.default.locations.tree_root.id` is `168` in `app/config/parameters.yml`: both branches ship
that value since 2026-10-05 (released in `v2.5.0.7` and `1.0.0.11`; `master` shipped 2 before), and the release `v2.5.0.6` does not define the
parameter at all
([chapter 4](04-installing.md#step-1-database-settings-in-parametersyml)). The Layouts concepts of this chapter apply
unchanged.

The CJW content is a different site from the Media Site demo. It uses the Media Site's `ng_*` content types (an older
set, with `ng_author`, `ng_feedback_form` and `ng_gallery_intro` and without the component types) plus its own
`cjw_*` types (`cjw_banner_head`, `cjw_content_embedded`, `cjw_feedback_form`, `cjw_folder_management`) and `tmv_*`
types for events, dates and categories; the default siteaccess `de` lists ger-DE before eng-GB. The figures of sections
5.2 to 5.8 (290 items, 21 layouts, 18 rules) do not apply to it.

## 5.11 From demo to your own site

You rarely keep the demo as it is. In order of effort:

1. **Keep the structure, change the content.** Edit the pages and articles, keep the layouts and mappings. Change
   `site_domain`, the Google Tag Manager code (`ngsite.default.site_settings.google_tag_manager_code` is Netgen's demo
   code in `dev`) and the mail addresses in `config/app/server/dev/app.yaml`. With `SERVER_ENVIRONMENT=prod`, the
   `prod.yaml` of the 1.2.0.x and 1.3.0.x branches takes them from `SITE_DOMAIN`, `COLLECTED_INFO_SENDER`,
   `COLLECTED_INFO_RECIPIENT` and `GOOGLE_TAG_MANAGER_CODE` in `.env.local` (empty: no tracking snippet), and the
   1.1.0.x one from `APP_DOMAIN`, `MAIL_FROM`, `MAIL_TO` and `GTM_CODE`.
2. **Remove one site.** If you only want Fit & Healthy, remove `bold_eng` and `bold_ger` from the siteaccess list,
   delete the Bold Agency subtree, and delete the Bold layouts and their mappings.
3. **Start from a clean repository.** Install with `exponential-oss` (1.3.0.x, and on the 2.5 generation through the
   kernel fork) or `netgen-media-clean` (where it is registered) for the platform content without the demo, and build
   content types, layouts and mappings yourself. A clean repository's site root is location 2, so set the tree root
   parameters of your line to 2. The
   designs and the `ng_*` view configuration still expect the Media Site content types; keep them or replace the
   configuration as well.

Whatever you change, change it on a development copy, export what you need (the Layout Manager exports layouts as
JSON), and move it to production deliberately; the install commands are not a deployment tool
([chapter 4](04-installing.md#411-running-the-install-again)).

## References

In this repository:

- `config/app/packages/` (siteaccesses, designs), `config/app/prepends/` (content views, Layouts blocks and views),
  `config/app/server/` (location IDs), `config/packages/netgen_layouts.yaml`, `templates/themes/`, `templates/nglayouts/`
  on the 1.1.0.x to 1.3.0.x branches; `app/config/ezplatform_siteaccess.yml` and `src/AppBundle/Resources/config/` on
  the 2.5 generation.
- [doc/netgen/SEARCH_SUGGESTIONS.md](../netgen/SEARCH_SUGGESTIONS.md), [doc/netgen/TRANSLATIONS.md](../netgen/TRANSLATIONS.md).
- Chapters [8. Configuration](08-configuration.md), [9. Front end and themes](09-frontend-and-themes.md),
  [10. Operations](10-operations.md).

External:

- Netgen Layouts: [documentation](https://docs.netgen.io/projects/layouts/en/latest/),
  [main concepts](https://docs.netgen.io/projects/layouts/en/latest/overview/main_concepts.html),
  [custom layout types](https://docs.netgen.io/projects/layouts/en/latest/cookbook/custom_layout_types.html),
  [custom blocks](https://docs.netgen.io/projects/layouts/en/latest/cookbook/custom_blocks.html),
  [overriding templates](https://docs.netgen.io/projects/layouts/en/latest/tips_tricks/override_existing_templates.html),
  [console commands](https://docs.netgen.io/projects/layouts/en/latest/reference/symfony_commands.html).
- Netgen Site API: [documentation](https://docs.netgen.io/projects/site-api/en/latest/),
  [configuration](https://docs.netgen.io/projects/site-api/en/latest/reference/configuration.html),
  [query types](https://docs.netgen.io/projects/site-api/en/latest/reference/query_types.html),
  [templating](https://docs.netgen.io/projects/site-api/en/latest/reference/templating.html).
- Netgen Media Site: [documentation](https://docs.netgen.io/projects/media-site/en/latest/),
  [source](https://github.com/netgen/media-site), [demo data](https://github.com/netgen/media-site-data).
- Netgen Tags: [github.com/netgen/TagsBundle](https://github.com/netgen/TagsBundle).
- Upstream concepts: [content model](https://doc.ibexa.co/en/5.0/content_management/content_model/),
  [the admin panel](https://doc.ibexa.co/en/latest/administration/admin_panel/).

[Contents](README.md) · Previous: [4. Installing](04-installing.md) · Next: [6. Serving the site](06-serving-the-site.md)
