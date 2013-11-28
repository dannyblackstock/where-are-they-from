from SPARQLWrapper import SPARQLWrapper, JSON

sparql = SPARQLWrapper("http://dbpedia.org/sparql")
sparql.setQuery("""
    PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
    PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
    PREFIX foaf: <http://xmlns.com/foaf/0.1/>
    
    SELECT DISTINCT ?film_title ?star_name
    where {?film_title rdf:type <http://dbpedia.org/ontology/Film> .
    ?film_title  foaf:name ?film_name .
    ?film_title rdfs:comment ?film_abstract .
    ?film_title dbpedia-owl:starring ?star .
    ?star dbpprop:name ?star_name
    }
    LIMIT 5
""")
sparql.setReturnFormat(JSON)
results = sparql.query().convert()


for result in results["results"]["bindings"]:
    print("film_title=" + result["film_title"]["value"] + "\nstar_name=" + result["star_name"]["value"])