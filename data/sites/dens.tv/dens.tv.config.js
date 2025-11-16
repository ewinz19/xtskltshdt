const dayjs = require('dayjs')
const utc = require('dayjs/plugin/utc')                                          const timezone = require('dayjs/plugin/timezone')
const customParseFormat = require('dayjs/plugin/customParseFormat')              const fs = require('fs')
const axios = require('axios')                                                   
dayjs.extend(utc)                                                                dayjs.extend(timezone)
dayjs.extend(customParseFormat)                                                  
const tz = 'Asia/Makassar'                                                       
module.exports = {                                                                 site: 'dens.tv',
  days: 2,                                                                       
  // URL untuk grab EPG                                                            url({ channel, date }) {
    return `https://www.dens.tv/api/dens3/tv/TvChannels/listEpgByDate?date=${date.format(
      'YYYY-MM-DD'                                                                   )}&id_channel=${channel.site_id}&app_type=10`
  },                                                                             
  // Parser EPG per channel                                                        parser({ content }) {
    const programs = []                                                              if (!content) return programs
                                                                                     let response
    try {                                                                              response = JSON.parse(content)
    } catch (e) {                                                                      console.error('❌ JSON parse error:', e)
      return programs                                                                }
                                                                                     if (!Array.isArray(response?.data)) return programs
                                                                                     response.data.forEach(item => {
      const title = item.title                                                         const match = title.match(/(S(\d+))?\s*(Ep|Episode)?\s*(\d+)?/i) || []
      const season = match[2] ? parseInt(match[2]) : null                              const episode = match[4] ? parseInt(match[4]) : null
                                                                                       programs.push({
        title,                                                                           description: item.description,
        season,                                                                          episode,
        start: dayjs.tz(item.start_time, 'YYYY-MM-DD HH:mm:ss', tz),                     stop: dayjs.tz(item.end_time, 'YYYY-MM-DD HH:mm:ss', tz)
      })                                                                             })
                                                                                     return programs
  },                                                                             
  // Ambil list channel Dens TV                                                    async channels() {
    const categories = {                                                               local: 1,
      premium: 2,                                                                      international: 3
    }                                                                            
    const result = []                                                            
    for (const id_category of Object.values(categories)) {                             let data
      try {                                                                              data = await axios.get('https://www.dens.tv/api/dens3/tv/TvChannels/listByCategory', {
          params: { id_category }                                                        }).then(r => r.data)
      } catch (e) {
        console.error('❌ Channels API error:', e)
        continue
      }

      if (!Array.isArray(data?.data?.contents)) continue

      data.data.contents.forEach(item => {
        const name = item.meta.title || 'Unknown'
        // xmltv_id aman, hapus karakter non-alfanumerik
        const xmltv_id_safe = name.replace(/[^a-zA-Z0-9]/g, '') + '.id' || item.meta.id
        result.push({                                                                      lang: 'id',
          site_id: item.meta.id,
          name,
          xmltv_id: xmltv_id_safe
        })
      })
    }

    return result                                                                  },                                                                                                                                                                // Simpan XML channels langsung
/*  async saveChannelsXML(outputPath = './dens.tv_id.channels.xml') {
    const channels = await this.channels()
    if (!channels.length) {
      console.error('❌ Channels kosong, tidak bisa membuat XML')
      return
    }

    let xml = '<?xml version="1.0" encoding="UTF-8"?>\n<channels>\n'
    for (const ch of channels) {
      xml += `  <channel site="dens.tv" site_id="${ch.site_id}" lang="${ch.lang}" xmltv_id="${ch.xmltv_id}">${ch.name}</channel>\n`                                   }
    xml += '</channels>\n'

    fs.writeFileSync(outputPath, xml, 'utf-8')
    console.log(`✅ File ${outputPath} berhasil dibuat, total ${channels.length} channel`)
  }
}*/


async saveChannelsXML(outputPath = './dens.tv_id.channels.xml', lang = 'id') {
  const channels = await this.channels()
  if (!channels.length) {
    console.error(`❌ Channels ${lang} kosong, tidak bisa membuat XML`)
    return
  }
                                                                                   let xml = '<?xml version="1.0" encoding="UTF-8"?>\n<channels>\n'
  for (const ch of channels) {
    // Escape nama channel untuk XML
    const safeName = ch.name
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')

    // xmltv_id hanya huruf, angka, titik, underscore
    const safeXmltvId = ch.name.replace(/[^a-zA-Z0-9._-]/g, '') || ch.site_id
    xml += `  <channel site="dens.tv" site_id="${ch.site_id}" lang="${lang}" xmltv_id="${safeXmltvId}.id">${safeName}</channel>\n`
  }
  xml += '</channels>\n'

  const fs = require('fs')
  fs.writeFileSync(outputPath, xml, 'utf-8')
  console.log(`✅ File ${outputPath} berhasil dibuat, total ${channels.length} channel (${lang})`)
}
}
