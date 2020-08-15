<template>
  <div class="foks_settings">
    <a-col class="block_col import_block" :span="8">
      <div class="title">{{text.title_import}}</div>
      <div class="import_block-link"></div>

      <div class="field-group">
        <a-input v-model="Foks.import" class="import-link" :placeholder="text.url"></a-input>
        <div class="statistic">
          <div v-if="total_count">Total products: <strong>{{total_count}}</strong></div>
          <div v-else-if="progress">Waiting for total products...
            <a-spin />
          </div>
          <div v-if="current_count">Loaded products: <strong>{{current_count}}</strong></div>
        </div>
        <a-progress class="progress" v-if="progress_count" :percent="+progress_count.toFixed(2)" status="active" />
        <a-button v-if="!progress" type="primary" class="import_now" @click="importFoks">{{text.import}}
        </a-button>
      </div>

      <div class="field-group">
        <div class="sub_title">{{text.update}}</div>
        <a-radio-group name="radioGroup" v-model="Foks.update">
          <a-radio value="1">1h</a-radio>
          <a-radio value="4">4h</a-radio>
          <a-radio value="24">24h</a-radio>
        </a-radio-group>
      </div>

      <div class="field-group">
        <div class="sub_title">{{text.img}}</div>
        <a-checkbox v-model="Foks.img">
          on/off
        </a-checkbox>
      </div>

      <div class="field-group">
        <a-button class="save_settings" type="primary" @click="saveSettings">{{text.save}}</a-button>
      </div>

    </a-col>

    <a-col class="block_col export_block" :span="12">
      <div class="title">{{text.title_export}}</div>
      <div class="field-group">
        <div v-if="!export_spin" class="export_block-link stable">Stable xml: <a target="_blank" :href="Foks.logs_url+text.export">{{Foks.logs_url}}{{text.export}}</a>
        </div>
        <a-spin v-else />
        <hr>
        <a-button v-if="!export_spin" :data-url="Foks.export" type="primary" @click="ExportFoks">{{text.export_now}}
        </a-button>
        <a-spin v-else />
      </div>
    </a-col>

  </div>
</template>

<script>
    // big
    // https://my.foks.biz/s/pb/f?key=547d2e64-c4b9-417e-bd28-3760c25409cd&type=yml_catalog&ext=xml
    // short
    // https://my.foks.biz/s/pb/f?key=547d2e64-c4b9-417e-bd28-3760c25409cd&type=drop_foks&ext=xml
    export default {
        name: "Settings",
        data() {
            return {
                progress: false,
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
                products_error: ''
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
        methods: {
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
                    action: 'importFoks',
                };
                this.$message.config({
                    top: '50px',
                    duration: 2
                });

                this.progress = true;
                this.$store.dispatch('sendRequest', request).then(res => {
                    console.log('importFoks', res.data);
                    this.progress = false;
                    if (res.data.success) {
                        this.$message.success({content: this.text.success});
                    }
                }).catch(error => {
                    this.progress = false;
                    this.error = true;
                    console.log(error);
                    this.$message.error({content: 'Error'});
                });

                this.checkTotal();

            },
            checkTotal() {
                if (!this.total_count) {
                    this.$store.dispatch('get', {url: this.Foks.logs_url + 'total.json'}).then(res => {
                        console.log(res.data);
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
                this.$store.dispatch('get', {url: this.Foks.logs_url + 'current.json'}).then(res => {
                    let current_count = res.data;
                    this.current_count = res.data;
                    this.progress_count = (current_count / this.total_count * 100);

                    if (current_count !== this.total_count && !this.error) {
                        this.checkProgress();
                    }

                }).catch(error => {
                    console.log(error);
                });
            },
            saveSettings() {
                const request = {
                    action: 'saveSettings',
                    data: this.Foks
                };
                this.$message.config({
                    top: '50px',
                    duration: 2
                });
                this.$store.dispatch('sendRequest', request).then(res => {
                    console.log(res);
                    this.$message.success({content: this.text.saved});
                });
            }
        },
    }
</script>

<style lang="scss" scoped>
  .foks_settings {

    .statistic {
      margin-top: 30px;
    }

    .save_settings {
      background: rgb(116, 187, 90);
      border: 1px solid;

      &:hover {
        opacity: .8;
      }
    }

    .block_col {
      padding: 30px;

      .title {
        font-weight: bold;
        font-size: 18px;
        margin-bottom: 20px;
      }

      .sub_title {
        font-weight: bold;
        margin-bottom: 10px;
      }

      .field-group {
        margin-bottom: 30px;
      }
    }

    .field_progress {
      margin-bottom: 20px;
    }

    .progress {
      margin-top: 30px;
      margin-left: 40px;
    }

    .export_block {
      &-link {
        display: inline-block;
        padding: 10px;
        background: #eee;
      }
    }
  }

</style>
