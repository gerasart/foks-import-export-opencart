<template>
  <div class="foks_settings">
    <a-col class="block_col import_block" :span="12">
      <div class="title">{{ text.title_import }}</div>
      <div class="import_block-link"></div>

      <div class="field-group">
        <a-input v-model="Foks.import" class="import-link" :placeholder="text.url"></a-input>
        <div class="statistic">
          <div v-if="total_count">Total products: <strong>{{ total_count }}</strong></div>
          <div v-else-if="progress">Waiting for total products...
            <a-spin />
          </div>
          <div v-if="current_count">Loaded products: <strong>{{ current_count }}</strong></div>
        </div>
        <a-progress class="progress" v-if="progress_count" :percent="+progress_count.toFixed(2)" status="active" />
        <a-button v-if="!progress && Foks.import && !reload" type="primary" class="import_now" @click="importFoks">
          {{ text.import }}
        </a-button>
        <a-button v-if="reload" @click="reloadPage">Reload page</a-button>
      </div>

      <div class="field-group">
        <div class="sub_title">{{ text.update }}</div>
        You can use your server cron jobs
        <br>
        <strong>Use this link</strong>
        <br>
        <code>{{ locationOrigin() }}/index.php?route=tool/foks_cron</code>

      </div>

      <div class="field-group">
        <div class="sub_title">{{ text.img }}</div>
        <a-checkbox v-model="Foks.img">
          on/off
        </a-checkbox>
      </div>

      <div class="field-group">
        <a-button class="save_settings" type="primary" @click="saveSettings">{{ text.save }}</a-button>
      </div>

    </a-col>

    <a-col class="block_col export_block" :span="8">
      <div class="title">{{ text.title_export }}</div>
      <div class="field-group">
        <a target="_blank" href="/index.php?route=tool/foks">
          {{ text.export }}
        </a>
      </div>
    </a-col>

  </div>
</template>

<script>

export default {
  name: "Settings",
  data() {
    return {
      progress: false,
      url: 'index.php?route=tool/foks/',
      text: {
        title_import: 'Import',
        title_export: 'Export',
        success: 'Import success',
        save: 'Save settings',
        import: 'Import now',
        export_now: 'Export now',
        saved: 'Saved!',
        update: 'Import/Export auto update',
        url: 'Import url',
        img: 'Import without img',
        export: 'foks_export.xml'
      },
      progress_count: 0,
      current_count: 0,
      total_count: 0,
      error: false,
      export_spin: false,
      products_error: '',
      logs_url: '/admin/view/javascript/app/logs/',
      token: "",
      reload: false
    }
  },
  computed: {
    Foks: {
      get() {
        return this.$store.state.foks;
      },
      set(value) {
        this.$store.commit('setter', {foks: value})
      }
    }
  },
  mounted() {
    this.Foks = window.foks;
    this.getToken();
  },
  methods: {
    locationOrigin() {
      return location.origin;
    },
    reloadPage() {
      location.reload()
    },
    getToken() {
      let this_token = this.Foks.token;
      if (!this.Foks.version3) {
        this.token = `&token=${this_token}`;
      } else {
        this.token = `&user_token=${this_token}`;
      }
      this.colorLog('log path: ' + this.logs_url, 'info')
    },
    ExportFoks() {
      this.export_spin = true;
      this.$store.dispatch('get', {url: this.Foks.export}).then(res => {
        console.log(res);
        this.export_spin = false;
      }).catch(error => {
        console.log(error);
        this.export_spin = false;
      });
    },
    importFoks() {
      const request = {
        url: this.url + 'ajaxImportFoks' + this.token,
      };
      this.$message.config({
        top: '50px',
        duration: 2
      });

      this.progress = true;
      this.$store.dispatch('send', request).then(res => {
        this.colorLog(res, 'info')

        if (res.data.success && this.current_count === this.total_count) {
          this.progress = false;
          this.reload = true;
          this.$message.success({content: this.text.success});
        } else {
          this.error = true;
          this.reload = true;
          this.$message.warning({content: 'Forse end'});
          setTimeout(() => {
            this.current_count = this.total_count
            this.progress_count = 100;
          }, 1000)
        }

      }).catch(error => {
        this.progress = false;
        this.error = true;
        this.$message.error({content: 'Error' + JSON.stringify(error)});
      });

      this.checkTotal();
    },
    checkTotal() {
      if (!this.total_count && this.progress) {
        this.$store.dispatch('get', {url: this.logs_url + 'total.json'}).then(res => {
          this.colorLog(res.data, 'info')
          this.total_count = res.data;
          if (!this.total_count && !this.error) {
            this.checkTotal();
          } else {
            if (!this.error) {
              this.checkProgress();
            }
          }
        }).catch(error => {
          if (error) {
            this.checkTotal();
          }
        });
      }
    },
    checkProgress() {
      this.$store.dispatch('get', {url: this.logs_url + 'current.json'}).then(res => {
        this.colorLog(res, 'info')

        let current_count = res.data;
        this.current_count = res.data;
        this.progress_count = (current_count / this.total_count * 100);

        if (current_count !== this.total_count && !this.error) {
          this.checkProgress();
        }

      }).catch(error => {
        this.colorLog(error, 'error')
      });
    },
    saveSettings() {
      const request = {
        url: this.url + 'ajaxSaveSettings' + this.token,
        data: this.Foks
      };
      this.$message.config({
        top: '50px',
        duration: 2
      });
      this.$store.dispatch('send', request).then(res => {
        this.colorLog(res, 'success')
        this.$message.success({content: this.text.saved});
        location.reload();
      });
    }
  },
}
</script>

<style lang="scss">

</style>
