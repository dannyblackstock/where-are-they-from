# to get user path on all operating systems
from os.path import expanduser


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
