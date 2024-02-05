# Documentation Plugins

**[Default Plugin](https://github.com/Dylanolivro/doc_plugins/blob/main/default.php)**

**[JSON Default Plugin](https://github.com/Dylanolivro/doc_plugins/blob/main/json_default.php)**

## Utilisation de cURL lorsque file_get_contents ne fonctionne pas

La fonction `file_get_contents_curl` est une alternative à `file_get_contents` qui utilise cURL pour récupérer le contenu d'une URL.

```php
function file_get_contents_curl($url)
{
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);

    $data = curl_exec($ch);
    curl_close($ch);

    return $data;
}
```

**Utilisation :**
Pour utiliser cette fonction, remplacez simplement :

```php
$content = file_get_contents($url);
```

par :

```php
$content = file_get_contents_curl($url);
```

## Optimisation du chargement des articles

Si le nombre d'articles est trop élevé (par exemple 300), on peut optimiser le chargement en coupant le code HTML pour récupérer moins de code. Cela accélère le chargement. Ensuite, on ajuste simplement le nombre d'articles récupérés avec `array_slice`.

```php
    $content = file_get_contents($url);
    $content = substr($content, 0, 80000);

    /* transformation en objet dom parsable */
    $content = str_get_html($content);
    $articles_path = '.t-entry-text';
    $title_path = 'h3';
    $url_path = 'a';
    $date_path = '.t-entry-date';

    $divs = array_slice($content->find($articles_path), 0, 15);
```

**Notez que le nombre '80000' est arbitraire et doit être ajusté en fonction de la taille du contenu que vous récupérez.**

## Nettoyage de la chaîne de caractères

Cette fonction est utilisée pour nettoyer les chaînes de caractères, en particulier pour `title` et `abstract`.

```php
$item->_Titre_ = MAPI_Tools::String_Clean(trim(html_entity_decode($div->find($title_path, 0)->plaintext, ENT_QUOTES, 'utf-8')));
```

## Complétion des URL incomplètes

Cette fonction permet d'ajouter le début de l'URL pour les URL incomplètes. Il faut faire attention à la construction de l'URL, parfois un '/' à la fin de l'URL est nécessaire.

```php
$url = 'https://academic.oup.com/eurpub/advance-articles?login=false';

function crawl($url, $items){
    ...

    $url = trim(htmlspecialchars(html_entity_decode($div->find($url_path, 0)->href, ENT_COMPAT, 'utf-8'), ENT_COMPAT, "utf-8"));
    if (strpos($url, "https://academic.oup.com") !== false) {
        $item->_URL = $url;
    } else {
        $item->_URL = "https://academic.oup.com" . $url;
    }
    ...
}
```

## Gestion des articles sans résumé

Dans certains cas, tous les articles n’ont pas de résumé. Pour gérer cela, vous pouvez vérifier si le résumé existe avant de le nettoyer.

```php
if (isset($div->find($abstract_path, 0)->plaintext)) {
    $item->_Abstract = MAPI_Tools::String_Clean(trim(html_entity_decode($div->find($abstract_path, 0)->plaintext, ENT_QUOTES, 'utf-8')));
}
```

**Notez que vous pouvez faire la même chose pour les dates**

## Récupération de texte dans une balise contenant une autre balise

Cette fonction permet de récupérer du texte dans une balise qui contient une autre balise. Par exemple, pour récupérer le texte après le titre dans le code HTML suivant :

```php
<div class="card-type-1__content">
    <div>
        <h3 class="h3-like card-type-1__title">Tensions d’approvisionnement : le rapport 2023 du GPUE</h3>

        Le Groupement pharmaceutique de l'Union européenne (GPUE), association européenne de la pharmacie d’officine, a publié cette semaine son enquête 2023 sur les ruptures d'approvisionnement en médicaments et dispositifs médicaux à l'officine.
    </div>

</div>
```

Vous pouvez utiliser le code suivant :

```php
$abstract_path = '.card-type-1__content div';

$h3 = $div->find($abstract_path,0)->children(0); // Récupère le h3
$abstract = MAPI_Tools::String_Clean(trim(html_entity_decode($h3->next_sibling()->plaintext, ENT_QUOTES, 'utf-8')));
var_dump($abstract);
```

### Gestion des espaces blancs, des sauts de ligne ou d’autres nœuds invisibles

Si des espaces blancs, des sauts de ligne ou d’autres nœuds invisibles sont présents, vous pouvez utiliser le code suivant pour les gérer :

```php
$divNode = $div->find($abstract_path,0);
    $abstract = '';

    foreach ($divNode->nodes as $node) {
        if ($node->tag == 'text') {
            $abstract .= MAPI_Tools::String_Clean(trim(html_entity_decode($node->plaintext, ENT_QUOTES, 'utf-8')));
        }
    }

    $item->_Abstract = $abstract;
```

**Explication** : Ce code parcourt tous les nœuds de la div ciblée. Si un nœud est un nœud de texte, il nettoie le texte et l’ajoute à la variable $abstract. À la fin, `$abstract` contient tout le texte de la div, nettoyé et sans nœuds invisibles.

## Contournement des problèmes de chargement et de blocage de connexion

Si vous rencontrez des problèmes de chargement infini ou si le site bloque la connexion, vous pouvez utiliser le service mytwip pour récupérer le contenu. Il suffit de préfixer l’URL avec l’adresse du service mytwip comme suit :

```
https://api.mytwip.com/v3.9/get_dev_content.php?url=VOTRE_URL
```

Remplacez `VOTRE_URL` par l’URL du site que vous souhaitez accéder. Cela peut aider à contourner les problèmes de chargement ou de blocage de la connexion.


## Récupération de la date d’un article

La fonction `get_date` permet de récupérer la date d’un article en accédant directement à l’URL de l’article. Cependant, cette opération peut ralentir le plugin car elle nécessite un accès supplémentaire à chaque URL d’article.
 
```php
function get_date($url){
    $content = file_get_contents($url);
    /* transformation en objet dom parsable */
    $content = str_get_html($content);

    $date = $content->find(".publish time", 0)->datetime;
    return $date;
}

function crawl($url, $items){
    ...

    $date = get_date($item->_URL);
    $item->_Date = MYNEWS::DATE_Conversion($date);

    ...
}
```
