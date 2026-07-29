# Staging deployment residue

Project Shield S1 compared the 436-file Release 1.0 artifact with the
non-mutable files deployed by staging deployment
`c9503121-c2f6-489b-b97c-0fb18d03ff6e`.

The following eight files were present in `/home/site/wwwroot` but were not
members of the release artifact:

| Relative path | Bytes | SHA-256 | Classification |
| --- | ---: | --- | --- |
| `hostingstart.html` | 4,554 | `510907465c0331a86d75dd645888293b5d07dd3eaf8d5cd0c3e909a7c7b5f594` | Harmless Azure App Service default hosting placeholder; pre-existing Azure-created residue. |
| `rc1-apps-temp.json` | 387 | `56d91eaad927494265fe32491d4eddc7ca4a656c2f8f770649d0412c0b0d6819` | Pre-existing RC deployment/application inventory output; non-executable release-audit residue. |
| `rc1-final-report-temp.txt.gz` | 1,068 | `72a14140a7dcf247cd741cfcfa17d1fda0e5cbe52925d0ff53859a751a386e11` | Pre-existing compressed RC final-report output; non-executable release-audit residue. |
| `rc1-prod-fs-audit-temp.txt.gz` | 1,123 | `3ae536d59642e389b9eace18c0c46920117a00c16870fe7507d97ac3b968cd41` | Pre-existing compressed production filesystem audit output; non-executable release-audit residue. |
| `rc1-prod-fs-audit2-temp.txt.gz` | 1,201 | `b6a37906cb02373bb386d43695eb2901799b6dbbd3518dea74d4d7c54dbc89af0a` | Pre-existing compressed production filesystem audit output; non-executable release-audit residue. |
| `rc1-production-audit-temp.json` | 0 | `e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855` | Pre-existing empty RC production-audit output; non-executable release-audit residue. |
| `rc1-production-audit-temp.json.gz` | 793 | `e4e79a7d4744beff4839060f4a37740bd1e99a75288aa7a358c857ee9bd8af88` | Pre-existing compressed RC production-audit output; non-executable release-audit residue. |
| `rc1-production-audit2-temp.json.gz` | 429 | `ea60b84399a37f9ae263c0368facc0ddbdde0df1d97ee651719fc3542cda5bfc` | Pre-existing compressed RC production-audit output; non-executable release-audit residue. |

No file above is executable or application-like. None was deleted. Isolated
validation excludes these known residues when copying the deployed application
so its source set remains equivalent to the release artifact.
