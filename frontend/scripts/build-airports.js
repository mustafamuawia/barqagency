import { mkdir, writeFile } from 'node:fs/promises'
import { createRequire } from 'node:module'
import { dirname, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'

const require = createRequire(import.meta.url)
const airports = require('airport-codes/airports.json')
  .filter((airport) => airport.iata?.trim())
  .map(({ iata, name, city }) => ({ iata, name, city }))

const outputPath = resolve(dirname(fileURLToPath(import.meta.url)), '../public/airports.json')

await mkdir(dirname(outputPath), { recursive: true })
await writeFile(outputPath, JSON.stringify(airports))
