#!/usr/bin/env python3
"""
Génère un fichier EPUB 3 de démonstration (structure valide).
Usage : python samples/generate-demo-epub.py
"""

import uuid
import zipfile
from pathlib import Path

OUTPUT = Path(__file__).parent / "demo-ken-luamba.epub"
BOOK_ID = f"urn:uuid:{uuid.uuid4()}"

MIMETYPE = "application/epub+zip"

CONTAINER_XML = """<?xml version="1.0" encoding="UTF-8"?>
<container version="1.0" xmlns="urn:oasis:names:tc:opendocument:xmlns:container">
  <rootfiles>
    <rootfile full-path="OEBPS/content.opf" media-type="application/oebps-package+xml"/>
  </rootfiles>
</container>
"""

CONTENT_OPF = f"""<?xml version="1.0" encoding="UTF-8"?>
<package xmlns="http://www.idpf.org/2007/opf" version="3.0" unique-identifier="BookId" xml:lang="fr">
  <metadata xmlns:dc="http://purl.org/dc/elements/1.1/">
    <dc:identifier id="BookId">{BOOK_ID}</dc:identifier>
    <dc:title>Lumière du matin — Extrait de démonstration</dc:title>
    <dc:language>fr</dc:language>
    <dc:creator>Pasteur Ken Luamba</dc:creator>
    <dc:publisher>Éditions Ken Luamba</dc:publisher>
    <dc:date>2026</dc:date>
    <dc:description>Exemple EPUB généré pour illustrer la structure d'un livre numérique.</dc:description>
    <meta property="dcterms:modified">2026-06-14T00:00:00Z</meta>
  </metadata>
  <manifest>
    <item id="nav" href="nav.xhtml" media-type="application/xhtml+xml" properties="nav"/>
    <item id="cover" href="cover.xhtml" media-type="application/xhtml+xml"/>
    <item id="c1" href="chapter1.xhtml" media-type="application/xhtml+xml"/>
    <item id="c2" href="chapter2.xhtml" media-type="application/xhtml+xml"/>
    <item id="css" href="styles.css" media-type="text/css"/>
  </manifest>
  <spine>
    <itemref idref="cover"/>
    <itemref idref="c1"/>
    <itemref idref="c2"/>
  </spine>
</package>
"""

NAV_XHTML = """<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:epub="http://www.idpf.org/2007/ops" lang="fr">
<head>
  <meta charset="UTF-8"/>
  <title>Table des matières</title>
  <link rel="stylesheet" type="text/css" href="styles.css"/>
</head>
<body>
  <nav epub:type="toc" id="toc">
    <h1>Table des matières</h1>
    <ol>
      <li><a href="cover.xhtml">Couverture</a></li>
      <li><a href="chapter1.xhtml">Chapitre 1 — Une parole pour ce matin</a></li>
      <li><a href="chapter2.xhtml">Chapitre 2 — Marcher dans la foi</a></li>
    </ol>
  </nav>
</body>
</html>
"""

COVER_XHTML = """<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="fr">
<head>
  <meta charset="UTF-8"/>
  <title>Couverture</title>
  <link rel="stylesheet" type="text/css" href="styles.css"/>
</head>
<body class="cover">
  <section epub:type="cover">
    <h1>Lumière du matin</h1>
    <p class="subtitle">Extrait de démonstration</p>
    <p class="author">Pasteur Ken Luamba</p>
    <p class="publisher">Éditions Ken Luamba</p>
  </section>
</body>
</html>
"""

CHAPTER1_XHTML = """<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="fr">
<head>
  <meta charset="UTF-8"/>
  <title>Chapitre 1</title>
  <link rel="stylesheet" type="text/css" href="styles.css"/>
</head>
<body>
  <section epub:type="chapter">
    <h1>Chapitre 1 — Une parole pour ce matin</h1>
    <p>Chaque jour offre une page nouvelle. Ce n'est pas le passé qui écrit l'avenir, mais la décision que nous prenons aujourd'hui d'écouter, de croire et d'avancer.</p>
    <p>La foi n'efface pas les questions ; elle donne un sens au chemin. Même lorsque la route semble étroite, une parole juste peut devenir une lampe pour nos pas.</p>
    <blockquote>
      <p>« Que votre lumière luise devant les hommes. »</p>
    </blockquote>
    <p>Ce fichier EPUB est un <strong>exemple pédagogique</strong> : il montre comment un livre numérique est organisé en chapitres XHTML, reliés par un manifeste et une table des matières.</p>
  </section>
</body>
</html>
"""

CHAPTER2_XHTML = """<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="fr">
<head>
  <meta charset="UTF-8"/>
  <title>Chapitre 2</title>
  <link rel="stylesheet" type="text/css" href="styles.css"/>
</head>
<body>
  <section epub:type="chapter">
    <h1>Chapitre 2 — Marcher dans la foi</h1>
    <p>Marcher dans la foi, c'est accepter que la réponse ne soit pas toujours immédiate, tout en demeurant fidèle à l'appel reçu.</p>
    <p>Dans une plateforme comme Ken Luamba, un fichier EPUB permet au lecteur de :</p>
    <ul>
      <li>naviguer chapitre par chapitre ;</li>
      <li>adapter la taille du texte ;</li>
      <li>conserver sa progression de lecture.</li>
    </ul>
    <p>— Fin de l'extrait de démonstration —</p>
  </section>
</body>
</html>
"""

STYLES_CSS = """
body {
  font-family: Georgia, "Times New Roman", serif;
  line-height: 1.6;
  margin: 1.2em;
  color: #1a1a1a;
}

h1 {
  font-size: 1.6em;
  margin-bottom: 0.8em;
  color: #0f172a;
}

p {
  margin: 0 0 1em;
  text-align: justify;
}

blockquote {
  margin: 1.2em 1em;
  padding-left: 1em;
  border-left: 3px solid #2563eb;
  font-style: italic;
  color: #334155;
}

.cover {
  text-align: center;
  margin-top: 30%;
}

.cover h1 {
  font-size: 2em;
}

.subtitle {
  font-size: 1.1em;
  color: #475569;
}

.author, .publisher {
  margin-top: 2em;
  color: #64748b;
}

ul {
  margin: 0 0 1em 1.2em;
}
"""

FILES = {
  "mimetype": MIMETYPE,
  "META-INF/container.xml": CONTAINER_XML,
  "OEBPS/content.opf": CONTENT_OPF,
  "OEBPS/nav.xhtml": NAV_XHTML,
  "OEBPS/cover.xhtml": COVER_XHTML,
  "OEBPS/chapter1.xhtml": CHAPTER1_XHTML,
  "OEBPS/chapter2.xhtml": CHAPTER2_XHTML,
  "OEBPS/styles.css": STYLES_CSS,
}


def build_epub(output_path: Path) -> None:
  """
  Construit un EPUB valide (mimetype non compressé en tête de l'archive).

  @param output_path Chemin du fichier .epub à créer
  """
  output_path.parent.mkdir(parents=True, exist_ok=True)

  with zipfile.ZipFile(output_path, "w") as archive:
    archive.writestr(
      "mimetype",
      MIMETYPE,
      compress_type=zipfile.ZIP_STORED,
    )

    for name, content in FILES.items():
      if name == "mimetype":
        continue

      archive.writestr(name, content.strip(), compress_type=zipfile.ZIP_DEFLATED)

  print(f"EPUB généré : {output_path}")


if __name__ == "__main__":
  build_epub(OUTPUT)
