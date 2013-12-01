# to get user path on all operating systems
from os.path import expanduser

# to parse DBPedia SPARQL
from SPARQLWrapper import SPARQLWrapper, JSON

def cleanItunesArtistString(artistName):

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

with open(itunesLibraryPath) as itunesLibraryXML:
    
    for line in itunesLibraryXML:
        
        if "<key>Artist</key>" in line:
            artistName = line

            # clean the string
            artistName = cleanItunesArtistString(artistName)

            # add artist to the list, if it's not already
            if artistName not in artistList:
                artistList.append(artistName)

# sort in alphabetical order
artistList.sort()
print(artistList)

for artist in artistList:

    # insert underscores to retrieve proper DBpedia page
    artist = artist.replace (" ", "_")

    sparql = SPARQLWrapper("http://dbpedia.org/sparql")

    sparql.setQuery("""
        PREFIX property: <http://dbpedia.org/property/>
        PREFIX dbpedia: <http://dbpedia.org/resource/>
        
        SELECT DISTINCT ?band_name ?band_origin
        WHERE {
        dbpedia:"""+artist+""" foaf:name ?band_name .
        dbpedia:"""+artist+""" property:origin ?band_origin .
        }
        LIMIT 5
    """)
    sparql.setReturnFormat(JSON)
    
    # TO-DO: try different forms of the string (eg. lower case "the" and "and")    

    try:
        results = sparql.query().convert()

        # print all the results
        # for result in results["results"]["bindings"]:
        #     print("band_name = " + result["band_name"]["value"] + "\nband_origin = " + result["band_origin"]["value"] + "\n")

        bandName = results["results"]["bindings"][-1]["band_name"]["value"]
        bandOrigin = results["results"]["bindings"][-1]["band_origin"]["value"]

        # clean up origin string if it's a URL
        if "http://dbpedia.org/resource/" in bandOrigin:
            bandOrigin = bandOrigin.replace("http://dbpedia.org/resource/", "")
            bandOrigin = bandOrigin.replace("_", " ")

        print bandName + "\n" + bandOrigin + "\n"

    except:
        print("No location found for "+artist+"\n")