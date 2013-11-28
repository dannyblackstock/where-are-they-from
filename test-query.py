from SPARQLWrapper import SPARQLWrapper, JSON

sparql = SPARQLWrapper("http://dbpedia.org/sparql")
sparql.setQuery("""
    PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
    PREFIX dbpedia: <http://dbpedia.org/resource/>
    SELECT ?s ?p
    WHERE { ?s ?p dbpedia:Berlin_Berlin } LIMIT 5
""")
sparql.setReturnFormat(JSON)
results = sparql.query().convert()

print("subject and predicate of all triples with object == dbpedia:Berlin_Berlin")

for result in results["results"]["bindings"]:
    print("s=" + result["s"]["value"] + "\tp=" + result["p"]["value"])

print("loop over all subjects returned in first query:")
for result in results["results"]["bindings"]:
    sparql.setQuery("""
        PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
        PREFIX dbpedia: <http://dbpedia.org/resource/>
        SELECT ?p ?o
        WHERE { <""" + result["s"]["value"] +
          """> ?p ?o } LIMIT 10""")
    results2 = sparql.query().convert()
    for result2 in results2["results"]["bindings"]:
        print("p=" + result2["p"]["value"] + "\to=" + result2["o"]["value"])