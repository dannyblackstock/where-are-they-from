# to get user path on all operating systems
from os.path import expanduser

# to parse DBPedia SPARQL
from SPARQLWrapper import SPARQLWrapper, JSON

# browser controller
import webbrowser

import json

# change if you want to run the location search again, or just use the stored data in local JSON file
useStoredData = True

def cleanItunesArtistString(artistName):

    #clean the string
    artistName = artistName.replace("<key>Artist</key><string>", "")
    artistName = artistName.replace("</string>", "")
    artistName = artistName.replace("&#38;", "&")

    # strip weird characters from beginning and end of line
    artistName = artistName.strip()
    return artistName

def writeToJSON(data):
    print("writing to JSON")
    with open('artist_location_data.json', 'w') as outfile:
        json.dump(data, outfile)
    print("done writing to JSON")

def readFromJSON():
    print("Reading JSON from \"artist_location_data.json\"")
    try:
        artistDictionary = json.load(open('artist_location_data.json'))
        print("Read successful!\nResults:")
        print(artistDictionary)
        return artistDictionary
    except:
        print("Read of \"artist_location_data.json\" failed!")

def main():

    if useStoredData == True:
        aristDictionary = readFromJSON()
        print("aristDictionary is ready:")
        print(aristDictionary)

    # run if there is not already a JSON file with the artist and their locations stored.
    elif useStoredData == False:

        # path to user iTunes Library XML
        itunesLibraryPath = expanduser("~/Music/iTunes/iTunes Music Library.xml")

        artistList = []
        artistDictionary = {}

        with open(itunesLibraryPath) as itunesLibraryXML:
            
            for line in itunesLibraryXML:
                
                if "<key>Artist</key>" in line:
                    artistName = line

                    # clean the string
                    artistName = cleanItunesArtistString(artistName)

                    # add artist to the list
                    if artistName not in artistList:
                        artistList.append(artistName)

        # sort in alphabetical order
        artistList.sort()
        print(artistList)

        for artist in artistList:

            # insert underscores to retrieve proper DBpedia page
            artist = artist.replace (" ", "_")

            # query for a certain band

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

            # convert results to a list of dictionaries and stuff
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

                artistDictionary[bandName] = bandOrigin
                print artistDictionary

            except:
                print("No location found for "+artist+"\n")

        # create a JSON file to store this dictionary, so we don't have to keep generating it
        writeToJSON(artistDictionary)


# run the beast
main()