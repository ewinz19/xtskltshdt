const dayjs = require('dayjs')
const utc = require('dayjs/plugin/utc')
const timezone = require('dayjs/plugin/timezone')
const customParseFormat = require('dayjs/plugin/customParseFormat')
const fs = require('fs')
const axios = require('axios')

dayjs.extend(utc)
dayjs.extend(timezone)
dayjs.extend(customParseFormat)

const languages = { en: 'ENG', id: 'IND' }

module.exports = {
  site: 'visionplus.id',
  days: 2,

  // URL untuk grab EPG
  url({ date, channel }) {
    return `https://www.visionplus.id/managetv/tvinfo/events/schedule?language=${
      languages[channel.lang]
    }&serviceId=${channel.site_id}&start=${date.format('YYYY-MM-DD')}T00%3A00%3A00Z&end=${
      date.add(1, 'day').format('YYYY-MM-DD')
    }T00%3A00%3A00Z&view=cd-events-grid-view`
  },

  // Parser EPG per channel
  parser({ content, channel }) {
    const programs = []

    if (!content) return programs

    let json
    try {
      json = JSON.parse(content)
    } catch (e) {
      console.error(`❌ JSON parse error for channel ${channel.name}:`, e)
      return programs
    }

    if (!Array.isArray(json.evs)) return programs

    for (const ev of json.evs) {
      if (ev.sid !== channel.site_id) continue

      const title = ev.con?.loc?.[0]?.tit || ev.con?.oti || 'Unknown Title'
      const match = title.match(/S(\d+)\s*Ep\s*(\d+)/i) || []
      const season = match[1] ? parseInt(match[1]) : null
      const episode = match[2] ? parseInt(match[2]) : null
      const description = ev.con?.loc?.[0]?.syn || ev.con?.syn || null
      const categories = ev.con?.categories || []

      programs.push({
        title,
        description,
        categories,
        season,
        episode,
        start: dayjs(ev.sta),
        stop: dayjs(ev.end)
      })
    }

    return programs
  },

  // Ambil list channel VisionPlus
  async channels({ lang = 'id' }) {
    const headers = {
      "IRIS-DEVICE-CLASS": "mobile",
      "IRIS-DEVICE-TYPE": "android",
      "IRIS-HW-DEVICE-ID": "abcdef123456",
      "IRIS-OS-VER": "14",
      "User-Agent": "Mozilla/5.0"
    }

    const url = `https://www.visionplus.id/managetv/tvinfo/channels/get?language=${languages[lang]}`

    let json
    try {
      const res = await axios.get(url, { headers })
      json = res.data
    } catch (e) {
      console.error('❌ Channel API error:', e)
      return []
    }

    const result = []

    if (Array.isArray(json?.chs)) {
      for (const ch of json.chs) {
        const name = ch.loc?.[0]?.nam || 'Unknown'
        // xmltv_id aman, hapus karakter non-alfanumerik dan @*
        const xmltv_id_safe = name.replace(/[^a-zA-Z0-9]/g, '') || ch.sid
        result.push({
          lang,
          site_id: ch.sid,
          name,
          xmltv_id: `${xmltv_id_safe}.${lang}` // xmltv_id bersih
        })
      }
    }

    return result
  },

  // Simpan XML channels langsung sesuai bahasa
  async saveChannelsXML(outputPath = './visionplus.id_id.channels.xml', lang = 'id') {
    const channels = await this.channels({ lang })
    if (!channels.length) {
      console.error(`❌ Channels ${lang} kosong, tidak bisa membuat XML`)
      return
    }

    let xml = '<?xml version="1.0" encoding="UTF-8"?>\n<channels>\n'
    for (const ch of channels) {
      xml += `  <channel site="visionplus.id" site_id="${ch.site_id}" lang="${ch.lang}" xmltv_id="${ch.xmltv_id}">${ch.name}</channel>\n`
    }
    xml += '</channels>\n'

    fs.writeFileSync(outputPath, xml, 'utf-8')
    console.log(`✅ File ${outputPath} berhasil dibuat, total ${channels.length} channel (${lang})`)
  }
}
