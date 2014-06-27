import web

urls = (
  '/', 'Index',
  '/artist_data', 'ArtistData'
)

render = web.template.render('templates/')

class Index(object):
    def GET(self):
        greeting = "Hello World"
        return render.index(greeting = greeting)

class ArtistData(object):
    def GET(self):
        import json
        jsonArtistList = json.load(open('static/artist_location_data.json'))
        artist_list = [{"artist":"Men without hats","location":"Montreal"},{"artist":"U2","location":"Dublin, Ireland"}]
        return jsonArtistList

if __name__ == "__main__":
    app = web.application(urls, globals())
    app.run()