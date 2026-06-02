import http from '@/scripts/http'
import { defineStore } from 'pinia'
import { handleError } from '@/scripts/helpers/error-handling'

export const useLorryPartyProfileStore = (useWindow = false) => {
  const defineStoreFunc = useWindow ? window.pinia.defineStore : defineStore

  return defineStoreFunc('lorryPartyProfile', {
    state: () => ({
      profiles: [],
      totalProfiles: 0,
      current: null,
      selectAllField: false,
      selectedProfiles: [],
      latestFetchToken: 0,
    }),

    actions: {
      resetSelection() {
        this.selectAllField = false
        this.selectedProfiles = []
      },

      resetProfiles() {
        this.profiles = []
        this.totalProfiles = 0
        this.resetSelection()
      },

      setSelectAllState(value) {
        this.selectAllField = value
      },

      selectProfile(value) {
        this.selectedProfiles = value
        this.selectAllField =
          this.profiles.length > 0 &&
          this.selectedProfiles.length === this.profiles.length
      },

      selectAllProfiles() {
        if (this.selectedProfiles.length === this.profiles.length) {
          this.resetSelection()
          return
        }

        this.selectedProfiles = this.profiles.map((profile) => profile.id)
        this.selectAllField = true
      },

      fetchProfiles(params) {
        const fetchToken = ++this.latestFetchToken

        return new Promise((resolve, reject) => {
          http
            .get('/api/v1/lorry-party-profiles', { params })
            .then((response) => {
              if (fetchToken === this.latestFetchToken) {
                this.profiles = response.data.data
                this.totalProfiles = response.data.meta?.total || response.data.data.length
              }

              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      fetchProfile(id) {
        return new Promise((resolve, reject) => {
          http
            .get(`/api/v1/lorry-party-profiles/${id}`)
            .then((response) => {
              this.current = response.data.data
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      deleteProfile(id) {
        return new Promise((resolve, reject) => {
          http
            .delete(`/api/v1/lorry-party-profiles/${id}`)
            .then((response) => {
              this.profiles = this.profiles.filter((profile) => profile.id !== id)
              this.totalProfiles = Math.max(this.totalProfiles - 1, 0)
              this.selectedProfiles = this.selectedProfiles.filter(
                (profileId) => profileId !== id
              )
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      async deleteSelectedProfiles() {
        await Promise.all(this.selectedProfiles.map((id) => this.deleteProfile(id)))
        this.resetSelection()
      },

      saveProfile(payload, id = null) {
        const request = id
          ? http.put(`/api/v1/lorry-party-profiles/${id}`, payload)
          : http.post('/api/v1/lorry-party-profiles', payload)

        return new Promise((resolve, reject) => {
          request
            .then((response) => resolve(response))
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },
    },
  })()
}
