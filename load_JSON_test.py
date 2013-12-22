def readFromJSON(fileName):
    import json
    print("Reading JSON from \"" + fileName +"\"")
    try:
        artistDictionary = json.load(open(fileName))
        print("Read successful!\nResults:")
        print(artistDictionary)
        return artistDictionary
    except:
        print("Read of \"" + fileName + "\" failed!")

readFromJSON('artist_location_data.json')