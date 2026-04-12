import api from '../api'

export default new (class {
  constructor() {}

  async putFile(path: string, file: Blob) {
    const { data } = await api.get(`${path}/generete-url`)

    const res = await api.put(data.uploadUrl, file, {
      headers: { 'Content-Type': file.type },
    })

    await api.post(`${path}/upload-success`, {
      key: data.fileKey,
    })

    return data.fileKey
  }

  async getFile(path: string, id: string) {
    const { data } = await api.get(`${path}/${id}`)
    return data.signedUrl
  }
})()
