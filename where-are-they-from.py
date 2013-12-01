# to get user path on all operating systems
from os.path import expanduser

# to parse DBPedia SPARQL
from SPARQLWrapper import SPARQLWrapper, JSON

def cleanArtistString(artistName):

    #clean the string
    artistName = artistName.replace("<key>Artist</key><string>", "")
    artistName = artistName.replace("</string>", "")
    artistName = artistName.replace("&#38;", "&")

    # strip weird characters from beginning and end of line
    artistName = artistName.strip()
    return artistName



# path to user iTunes Library XML
itunesLibraryPath = expanduser("~/Music/iTunes/iTunes Music Library.xml")

artistList = []

with open(itunesLibraryPath) as search:
    
    for line in search:
        
        if "<key>Artist</key>" in line:
            artistName = line

            # clean the string
            artistName = cleanArtistString(artistName)

            # add artist to the list
            if artistName not in artistList:
                artistList.append(artistName)

artistList.sort()
print(artistList)

# to do origin and birthplace wikipedia


sparql = SPARQLWrapper("http://dbpedia.org/sparql")
sparql.setQuery("""
    PREFIX property: <http://dbpedia.org/property/>
    PREFIX dbpedia: <http://dbpedia.org/resource/>
    
    SELECT DISTINCT ?band_name ?band_origin
    WHERE {
    dbpedia:Aidan_Knight foaf:name ?band_name .
    dbpedia:Aidan_Knight property:origin ?band_origin .
    }
    LIMIT 5
""")
sparql.setReturnFormat(JSON)
results = sparql.query().convert()

print "\n\n"
print(results["results"]["bindings"][-1]["band_name"]["value"])
print(results["results"]["bindings"][-1]["band_origin"]["value"])
print "\n\n"

for result in results["results"]["bindings"]:
    print("band_name = " + result["band_name"]["value"] + "\nband_origin = " + result["band_origin"]["value"] + "\n")
